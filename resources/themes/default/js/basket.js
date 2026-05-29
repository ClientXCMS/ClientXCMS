const form = document.getElementById('basket-config-form');
const errorContainer = document.getElementById('basket-config-error');

const localeFor = (currency) => (currency === 'EUR' ? 'fr-FR' : 'en-US');

const formatMoney = (amount, currency) =>
    new Intl.NumberFormat(localeFor(currency), { style: 'currency', currency }).format(Number(amount ?? 0));

const setText = (id, value) => {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = value;
    }
};

const hideError = () => {
    if (errorContainer) {
        errorContainer.classList.add('hidden');
        errorContainer.textContent = '';
    }
};

const showError = (message) => {
    if (errorContainer) {
        errorContainer.classList.remove('hidden');
        errorContainer.textContent = message;
    }
};

const applySummary = (payload) => {
    const currency = payload.currency ?? window.currency ?? 'EUR';
    const formatted = payload.formatted ?? {};
    const totals = payload.totals ?? {};

    setText('recurring', formatted.recurring ?? formatMoney(totals.recurring_ht, currency));
    setText('onetime', formatted.onetime ?? formatMoney(totals.onetime_ht, currency));
    setText('fees', formatted.setup ?? formatMoney(totals.setup_ht, currency));
    setText('subtotal', formatted.subtotal ?? formatMoney(totals.first_payment_ht, currency));
    setText('taxes', formatted.taxes ?? formatMoney(totals.tax, currency));
    setText('total', formatted.total ?? formatMoney(totals.total, currency));

    // v2.16 — render the inline coupon line. The pricing service
    // returns `payload.coupon = { code, discount_ht, formatted_discount }`
    // when the basket-level coupon shaved off some money. Hide the
    // line when there's nothing to display so the layout stays clean.
    const couponLine = document.getElementById('coupon-line');
    if (couponLine) {
        const meta = payload.coupon;
        const discountHt = Number(totals.discount_ht || 0);
        if (meta && discountHt > 0) {
            couponLine.classList.remove('hidden');
            setText('coupon-code', meta.code ?? '');
            setText('coupon-discount', meta.formatted_discount ?? `-${formatMoney(discountHt, currency)}`);
        } else if (!meta) {
            // No coupon at all → hide.
            couponLine.classList.add('hidden');
        }
    }

    (payload.options ?? []).forEach((option) => {
        const key = option.key;
        setText(`options_price[${key}]`, option.formatted ?? formatMoney(option.amount_ht, currency));
        const nameElement = document.getElementById(`options_name[${key}]`);
        if (nameElement && option.label) {
            nameElement.textContent = option.label;
        }
    });
};

const debounce = (fn, delay = 200) => {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
};

const fetchPreview = async () => {
    if (!form) {
        return;
    }
    const endpoint = form.dataset.pricingEndpoint;
    if (!endpoint) {
        return;
    }
    const token =
        form.querySelector('input[name="_token"]')?.value ??
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const formData = new FormData(form);

    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(token ? { 'X-CSRF-TOKEN': token } : {}),
            },
            body: formData,
        });

        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            const message =
                payload.message ??
                (payload.errors ? Object.values(payload.errors).flat().join(' / ') : null) ??
                'Une erreur est survenue lors du calcul du prix.';
            showError(message);
            return;
        }
        if (payload.errors && Object.values(payload.errors).length > 0){
            const message = payload.errors ? Object.values(payload.errors).flat().join(' / ') : null;
            message ? showError(message) : '';
        } else {
            hideError();
        }
        applySummary(payload);
    } catch (e) {
        showError('Impossible de récupérer le prix pour le moment.');
    }
};

const debouncedFetch = debounce(fetchPreview, 150);

if (form) {
    ['change', 'input'].forEach((eventName) => {
        form.addEventListener(eventName, (event) => {
            if (event.target?.type === 'submit' || event.target?.type === 'button') {
                return;
            }
            debouncedFetch();
        });
    });

    document.addEventListener('DOMContentLoaded', fetchPreview);
}

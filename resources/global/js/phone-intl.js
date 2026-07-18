import intlTelInput from 'intl-tel-input/intlTelInputWithUtils';
import 'intl-tel-input/build/css/intlTelInput.css';

import * as itiEn from 'intl-tel-input/i18n/en';
import * as itiFr from 'intl-tel-input/i18n/fr';
import * as itiEs from 'intl-tel-input/i18n/es';

const I18N_BUNDLES = {
    en: pickBundle(itiEn),
    fr: pickBundle(itiFr),
    es: pickBundle(itiEs),
};

function pickBundle(mod) {
    return (mod && (mod.default ?? mod)) || {};
}

function getLocaleBundle() {
    const htmlLang = (document.documentElement?.lang || 'en').toLowerCase();
    // Accept both `fr` and `fr-FR`
    const short = htmlLang.split('-')[0];
    return I18N_BUNDLES[short] ?? I18N_BUNDLES.en;
}

const SELECTOR = 'input[data-phone-intl]:not([data-phone-intl-attached])';

function attach(input) {
    const initialCountry = (input.dataset.initialCountry || 'fr').toLowerCase();
    const allowedCountries = input.dataset.onlyCountries
        ? input.dataset.onlyCountries.split(',').map((c) => c.trim().toLowerCase())
        : undefined;

    const options = {
        initialCountry,
        separateDialCode: true,
        autoPlaceholder: 'polite',
        formatOnDisplay: true,
        i18n: getLocaleBundle(),
    };
    if (Array.isArray(allowedCountries) && allowedCountries.length > 0) {
        options.onlyCountries = allowedCountries;
    }
    const iti = intlTelInput(input, options);

    input.__itiInstance = iti;
    input.dataset.phoneIntlAttached = '1';

    const countrySelect = document.querySelector('select[name="country"]');
    if (countrySelect) {
        const syncFromSelect = () => {
            const v = (countrySelect.value || '').toLowerCase();
            if (v && v !== iti.getSelectedCountryData()?.iso2) {
                iti.setCountry(v);
            }
        };
        const syncFromIti = () => {
            const v = iti.getSelectedCountryData()?.iso2;
            if (v && countrySelect.value !== v.toUpperCase()) {
                countrySelect.value = v.toUpperCase();
                countrySelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
        };
        countrySelect.addEventListener('change', syncFromSelect);
        input.addEventListener('countrychange', syncFromIti);
        syncFromSelect();
    }

    const form = input.closest('form');
    if (form && !form.dataset.phoneIntlHooked) {
        form.dataset.phoneIntlHooked = '1';
        form.addEventListener('submit', () => {
            form.querySelectorAll('input[data-phone-intl]').forEach((el) => {
                try {
                    const itiInstance = el.__itiInstance;
                    if (itiInstance && typeof itiInstance.getNumber === 'function') {
                        const e164 = itiInstance.getNumber();
                        if (e164) {
                            el.value = e164;
                        }
                    }
                } catch (e) {
                }
            });
        });
    }
}

function attachAll(root = document) {
    root.querySelectorAll(SELECTOR).forEach(attach);
}

function bootstrap() {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => attachAll(), { once: true });
    } else {
        attachAll();
    }

    // Re-scan when the DOM mutates (modals, livewire, …).
    const target = document.body || document.documentElement;
    if (target) {
        const observer = new MutationObserver((records) => {
            for (const r of records) {
                r.addedNodes.forEach((n) => {
                    if (n.nodeType !== Node.ELEMENT_NODE) return;
                    if (n.matches?.(SELECTOR)) attach(n);
                    else if (n.querySelectorAll) attachAll(n);
                });
            }
        });
        observer.observe(target, { childList: true, subtree: true });
    }
}

bootstrap();

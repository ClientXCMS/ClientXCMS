/*
 * v2.16 — International phone input
 *
 * Picks up every <input data-phone-intl> on the page, attaches
 * intl-tel-input with a flag/dial-code dropdown on the LEFT, and rewrites
 * the input's value to E.164 just before the surrounding form submits so
 * the backend (propaganistas/laravel-phone) accepts it without further
 * massaging.
 *
 * Usage from Blade:
 *   <input type="tel"
 *          name="phone"
 *          value="{{ old('phone', $user?->phone) }}"
 *          data-phone-intl
 *          data-initial-country="{{ strtolower(old('country', $user?->country ?? 'fr')) }}">
 *
 * The companion partial resources/views/shared/phone-intl.blade.php
 * exposes this contract.
 */

import intlTelInput from 'intl-tel-input';
import 'intl-tel-input/build/css/intlTelInput.css';

const SELECTOR = 'input[data-phone-intl]:not([data-phone-intl-attached])';

function attach(input) {
    const initialCountry = (input.dataset.initialCountry || 'fr').toLowerCase();
    const allowedCountries = input.dataset.onlyCountries
        ? input.dataset.onlyCountries.split(',').map((c) => c.trim().toLowerCase())
        : undefined;

    const iti = intlTelInput(input, {
        initialCountry,
        separateDialCode: true,
        nationalMode: false,
        formatAsYouType: true,
        autoPlaceholder: 'polite',
        onlyCountries: allowedCountries,
        // Lazy-load the country data Web Service from a CDN only when the
        // user changes country. Avoids bloating the initial bundle.
        loadUtils: () => import('intl-tel-input/build/js/utils.js?url').then(
            (mod) => mod.default
        ),
    });

    input.dataset.phoneIntlAttached = '1';

    // Pair the input with the existing country <select name="country"> on the
    // page if any — switching country in the dropdown also updates the
    // intl-tel-input flag, and vice-versa. Lets the existing register/profile
    // forms stay coherent.
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

    // Normalise the value to E.164 on form submit so the backend always
    // sees the canonical international format. We do this even if the
    // user typed a national number — intl-tel-input computes the E.164
    // representation for us once utils.js has loaded.
    const form = input.closest('form');
    if (form && !form.dataset.phoneIntlHooked) {
        form.dataset.phoneIntlHooked = '1';
        form.addEventListener('submit', () => {
            form.querySelectorAll(SELECTOR.replace(':not([data-phone-intl-attached])', '')).forEach((el) => {
                // .replaceWith would lose the listener; mutate value in place.
                try {
                    const itiInstance = window.intlTelInputGlobals?.getInstance?.(el);
                    if (itiInstance) {
                        const e164 = itiInstance.getNumber();
                        if (e164) {
                            el.value = e164;
                        }
                    }
                } catch (e) {
                    // Swallow — the backend PhoneRule will give a clear error.
                }
            });
        });
    }
}

function attachAll(root = document) {
    root.querySelectorAll(SELECTOR).forEach(attach);
}

// v2.16 — <script type="module"> is deferred by the browser, so by the
// time this module's body executes the DOMContentLoaded event has often
// already fired. addEventListener() then never triggers because the
// event lives in the past — that was the regression hitting the live
// registration form. Detect the state and run the scan immediately.
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

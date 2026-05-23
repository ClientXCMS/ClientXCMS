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

// v2.16 — switched to the "with utils" entry which inlines utils.js
// at build time. Eliminates the dynamic import + loadUtils config that
// was incompatible with intl-tel-input v25 and triggered the
// "Cannot read properties of undefined (reading 'length')" runtime
// error on the live registration form.
import intlTelInput from 'intl-tel-input/intlTelInputWithUtils';
import 'intl-tel-input/build/css/intlTelInput.css';

// v2.16 — eagerly pull the intl-tel-input translation bundles for the
// locales ClientXCMS ships out of the box. Importing them statically
// keeps Vite's tree-shaking happy AND avoids a runtime fetch / extra
// HTTP roundtrip for the dropdown's "Search" placeholder etc.
import * as itiEn from 'intl-tel-input/i18n/en';
import * as itiFr from 'intl-tel-input/i18n/fr';
import * as itiEs from 'intl-tel-input/i18n/es';

const I18N_BUNDLES = {
    en: pickBundle(itiEn),
    fr: pickBundle(itiFr),
    es: pickBundle(itiEs),
};

function pickBundle(mod) {
    // intl-tel-input exports each locale as an ES module whose default
    // export carries the strings. Some sub-paths expose interface +
    // countries as a single combined object — read either shape.
    return (mod && (mod.default ?? mod)) || {};
}

/**
 * Pick the intl-tel-input translation bundle that best matches the
 * application's runtime locale. Falls back to English when the locale
 * is not supported by the lib.
 */
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

    // v2.16 — minimal viable init for intl-tel-input v25. Several
    // options used in our v23 prototype were silently dropped or
    // renamed in v25 and caused `TypeError: Cannot read properties
    // of undefined (reading 'length')` during construction:
    //   - `nationalMode`     → removed
    //   - `formatAsYouType`  → renamed `formatOnDisplay`
    //   - `loadUtils`        → renamed `loadUtilsOnInit`, different shape
    //
    // We sidestep all of this by relying on the "intlTelInputWithUtils"
    // import above (utils bundled at build time) and only passing the
    // options that survived intact.
    const options = {
        initialCountry,
        separateDialCode: true,
        autoPlaceholder: 'polite',
        formatOnDisplay: true,
        // v2.16 — surface the Search placeholder + country list + ARIA
        // labels in the user's language. The bundle is imported above
        // and falls back to English when an unsupported locale is set.
        i18n: getLocaleBundle(),
    };
    if (Array.isArray(allowedCountries) && allowedCountries.length > 0) {
        options.onlyCountries = allowedCountries;
    }
    const iti = intlTelInput(input, options);

    // v2.16 — keep a reference on the DOM node so the form-submit
    // serializer can grab it without depending on the v23
    // `intlTelInputGlobals` API that v25 removed.
    input.__itiInstance = iti;
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
            form.querySelectorAll('input[data-phone-intl]').forEach((el) => {
                try {
                    // v2.16 — instance is stashed on the input itself by attach().
                    const itiInstance = el.__itiInstance;
                    if (itiInstance && typeof itiInstance.getNumber === 'function') {
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

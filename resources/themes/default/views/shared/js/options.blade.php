<?php
/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2025
 */
?>

<script>

    function updateTitle(element, option, price, currency) {
        const locale = window.currency === 'EUR' ? 'fr-FR' : 'en-US';
        const formatter = new Intl.NumberFormat(locale, {
            style: 'currency',
            currency: currency
        });
        const type = option.type;
        const setupfee = price.setup;
        const recurring = price.recurringPayment;
        const title = option.title;
        const oneTime = price.onetimePayment;
        const per = window.per;
        let priceMessage = '';
        if (setupfee > 0) {
            if (type === 'slider') {
                if (option.step !== 1){
                    priceMessage += "(" + window.recurrings.setupfee +`: ${formatter.format(setupfee)} ${per} ${option.step} ${option.unit})`;
                } else {
                    priceMessage += "(" + window.recurrings.setupfee +`: ${formatter.format(setupfee)} ${per} ${option.unit})`;
                }
            } else {
                priceMessage += "(" + window.recurrings.setupfee +`: ${formatter.format(setupfee)})`;
            }
        }
        if (recurring > 0) {
            if (type === 'slider') {
                if (option.step !== 1){
                    priceMessage += "(" + window.recurrings.recurring + `: ${formatter.format(recurring)} ${per} ${option.step} ${option.unit})`;
                } else {
                    priceMessage += "(" + window.recurrings.recurring + `: ${formatter.format(recurring)} ${per} ${option.unit})`;
                }
            } else {
                priceMessage += "(" + window.recurrings.recurring + `: ${formatter.format(recurring)})`;
            }
        }
        if (oneTime > 0) {
            if (type === 'slider'){
                if (option.step !== 1){
                    priceMessage += "(" + window.recurrings.onetime +` : ${formatter.format(oneTime)} ${per} ${option.step} ${option.unit}) `;
                } else {
                    priceMessage += "(" + window.recurrings.onetime +` : ${formatter.format(oneTime)} ${per} ${option.unit}) `;
                }
            } else {
                priceMessage += "(" + window.recurrings.onetime +` : ${formatter.format(oneTime)}) `;
            }
        }
        if (type === 'text' || type === 'number' || type === 'textarea' || type === 'slider' || type === 'checkbox') {
            const label = document.querySelector(`label[for="${element.id}"]`);
            label.innerText = `${title} ${priceMessage}`;
        }
        if (type === 'radio') {
            const label = document.querySelector(`label[for="${element.id}"]`);
            label.innerText = `${title}  ${priceMessage}`;
        }
        if (type === 'dropdown') {
            element.innerText = `${title} ${priceMessage}`;
        }
    }

    function getPriceFromRecurring(option, recurring, currency, value = null)
    {
        let globalPricing = [];
        if (option.type === 'dropdown' || option.type === 'radio') {

            for (const [id, pricing] of Object.entries(option.pricing)) {
                if (value === id) {
                    let key = Object.keys(pricing)[0];
                    globalPricing = [[id, pricing[key]]];
                    break;
                }
            }
        } else {
            globalPricing = Object.entries(option.pricing);
        }

        function calculateTax(taxPercent, price) {
            return price * taxPercent / 100;
        }

        for (const [id, pricing] of globalPricing) {
            if (pricing == undefined) {
                continue;
            }
            if (pricing.currency === currency || currency === undefined) {
                const recurring_setup = `${recurring}_setup`;
                const recurringPayment = pricing[recurring];
                const setup = pricing[recurring_setup];
                if (recurringPayment === undefined) {
                    return getPriceFromRecurring(option, 'monthly', currency);
                }
                return {
                    setup: setup ?? 0,
                    recurringPayment: recurringPayment ?? 0,
                    tax: calculateTax(window.taxPercent, setup ?? 0 + recurringPayment ?? 0 + pricing.onetime ?? 0),
                    onetimePayment: pricing.onetime ?? 0
                };
            }
        }
        console.log('No pricing found for currency', currency, option, recurring);
        return {
            setup: 0,
            recurringPayment: 0,
            tax: 0,
            onetimePayment: 0
        };
    }

    function updateLabels(pricing, options) {
        let copy = JSON.parse(JSON.stringify(options));
        for (const [id, option] of options) {
            let price;
            if (option.type === 'checkbox') {
                const checkbox = document.querySelector(`input[name="options[${id}]`);
                price = getPriceFromRecurring(option, pricing.recurring, pricing.currency);
                updateTitle(checkbox, option, price, pricing.currency);
            }
            if (option.type === 'text' || option.type === 'number') {
                const input = document.querySelector(`input[name="options[${id}]"]`);
                price = getPriceFromRecurring(option, pricing.recurring, pricing.currency);
                updateTitle(input, option, price, pricing.currency);
            }
            if (option.type === 'textarea') {
                const textarea = document.querySelector(`textarea[name="options[${id}]"]`);
                price = getPriceFromRecurring(option, pricing.recurring, pricing.currency);
                updateTitle(textarea, option, price, pricing.currency);
            }
            if (option.type === 'radio') {
                const radios = document.querySelectorAll(`input[name="options[${id}]"]`);
                radios.forEach(function (radio) {
                    price = getPriceFromRecurring(option, pricing.recurring, pricing.currency, radio.getAttribute('data-radio-id'));
                    updateTitle(radio, option, price, pricing.currency);
                });
            }
            if (option.type === 'dropdown') {
                const select = document.querySelector(`select[name="options[${id}]"]`);
                if (select) {
                    const options = select.options;
                    let optiontmp;
                    for (let i = 0; i < options.length; i++) {
                        optiontmp = options[i];
                        price = getPriceFromRecurring(option, pricing.recurring, pricing.currency, optiontmp.getAttribute('data-dropdown-id'));
                        updateTitle(optiontmp, option, price, pricing.currency);
                    }
                }
            }
            if (option.type === 'slider') {
                const slider = document.querySelector(`input[name="options[${id}]"]`);
                if (slider) {
                    price = getPriceFromRecurring(option, pricing.recurring, pricing.currency);
                    updateTitle(slider, option, price, pricing.currency);
                }
            }
        }
    }

    function initOptions(pricing, options) {
        for (const [id, option] of options) {
            let price;
            if (option.type === 'checkbox') {
                const checkbox = document.querySelector(`input[name="options[${id}]"]`);
                checkbox.addEventListener('change', function () {
                    let pricingCopy = JSON.parse(JSON.stringify(pricing));

                    updateSummary(pricingCopy, options)
                });
            }
            if (option.type === 'text' || option.type === 'number') {
                const input = document.querySelector(`input[name="options[${id}]"]`);
                input.addEventListener('input', function () {
                    let pricingCopy = JSON.parse(JSON.stringify(pricing));
                    updateSummary(pricingCopy, options)
                });
            }
            if (option.type === 'textarea') {
                const textarea = document.querySelector(`textarea[name="options[${id}]"]`);
                textarea.addEventListener('input', function () {
                    let pricingCopy = JSON.parse(JSON.stringify(pricing));
                    updateSummary(pricingCopy, options)
                });
            }
            if (option.type === 'radio') {
                const radios = document.querySelectorAll(`input[name="options[${id}]"]`);
                radios.forEach(function (radio) {
                    radio.addEventListener('change', function () {
                        let pricingCopy = JSON.parse(JSON.stringify(pricing));
                        updateSummary(pricingCopy, options)
                    });
                });
            }
            if (option.type === 'dropdown') {
                const select = document.querySelector(`select[name="options[${id}]"]`);
                select.addEventListener('change', function () {
                    let pricingCopy = JSON.parse(JSON.stringify(pricing));
                    updateSummary(pricingCopy, options)
                })
            }
            if (option.type === 'slider') {
                const slider = document.querySelector(`input[name="options[${id}]"]`);
                slider.addEventListener('input', function () {
                    const pricingCopy = { ...pricing };
                    updateSummary(pricingCopy, options)
                })
            }
        }
    }

</script>

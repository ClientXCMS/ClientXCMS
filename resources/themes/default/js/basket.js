
class OptionItem {
    constructor(id, data, value) {
        this.id = id;
        this.title = data.title;
        this.type = data.type;
        this.pricing = data.pricing;
        this.step = data.step || 1;
        this.unit = data.unit || '';
        this.value = value;
    }

    setValue(value) {
        this.value = value;
    }

    formatPrice(price, currency) {
        const locale = window.currency === 'EUR' ? 'fr-FR' : 'en-US';
        return new Intl.NumberFormat(locale, {
            style: 'currency',
            currency: currency
        }).format(price);
    }

    getFirstPrice(currency, recurring) {
        let globalPricing = [];
        if (this.type === 'dropdown' || this.type === 'radio') {
            for (const [key, pricing] of Object.entries(this.pricing)) {
                if (value === key) {
                    const subKey = Object.keys(pricing)[0];
                    globalPricing = [[key, pricing[subKey]]];
                    break;
                }
            }
        } else {
            globalPricing = Object.entries(this.pricing);
        }
        const recurrings = window.recurrings;
        for (const [key, pricing] of globalPricing) {
            if (!pricing) continue;
            for (const recurringKey of recurrings) {
                if (pricing[recurringKey] !== undefined && pricing[recurringKey] !== null) {
                    return this.getPrice(recurringKey, currency, key);
                }
            }
        }
        return { setup: 0, recurringPayment: 0, tax: 0, onetimePayment: 0, onetimeSetup: 0 };
    }

    getPrice(recurring, currency, value = null) {
        let globalPricing = [];
        if (this.type === 'dropdown' || this.type === 'radio') {
            for (const [key, pricing] of Object.entries(this.pricing)) {
                if (value === key) {
                    const subKey = Object.keys(pricing);
                    globalPricing = [[key, pricing[subKey]]];
                    break;
                }
            }
        } else {
            globalPricing = Object.entries(this.pricing);
        }

        const calculateTax = (taxPercent, price) => price * taxPercent / 100;
        for (const [key, pricing] of globalPricing) {
            if (!pricing) continue;
            if (pricing.currency === currency || currency === undefined) {
                const recurringSetupKey = `setup_${recurring}`;
                const recurringPayment = recurring === 'onetime' ? 0 : pricing[recurring] ?? null;
                const setup = pricing[recurringSetupKey] ?? null;
                if (recurringPayment === null && recurring !== 'onetime') {
                    return this.getFirstPrice(currency, recurring);
                }
                return {
                    setup: setup / this.step,
                    recurringPayment: recurringPayment / this.step,
                    tax: calculateTax(window.taxPercent, (setup + recurringPayment + pricing.onetime ?? 0)),
                    onetimePayment: (pricing.onetime ?? 0) / this.step,
                };
            }
        }
        return { setup: 0, recurringPayment: 0, tax: 0, onetimePayment: 0, onetimeSetup: 0 };
    }

    updateTitleElement(element, pricing, currency) {
        const price = this.getPrice(pricing.recurring, currency, element.getAttribute('data-radio-id') || element.getAttribute('data-dropdown-id'));
        let priceMessage = '';
        let title = this.title;

        if (price.setup > 0) {
            if (this.type === 'slider' && this.step !== 1) {
                priceMessage += `- ${window.translations.setupfee}: ${this.formatPrice(price.setup * this.step, currency)} ${window.per} ${this.step} ${this.unit}`;
            } else {
                priceMessage += `- ${window.translations.setupfee}: ${this.formatPrice(price.setup, currency)}`;
            }
        }
        if (price.recurringPayment > 0) {
            if (this.type === 'slider' && this.step !== 1) {
                priceMessage += `- ${window.translations.recurring}: ${this.formatPrice(price.recurringPayment * this.step, currency)} ${window.per} ${this.step} ${this.unit}`;
            } else {
                priceMessage += ` - ${window.translations.recurring}: ${this.formatPrice(price.recurringPayment, currency)}`;
            }
        }
        if (price.onetimePayment > 0) {
            if (this.type === 'slider' && this.step !== 1) {
                priceMessage += `- ${window.translations.onetime}: ${this.formatPrice(price.onetimePayment * this.step, currency)} ${window.per} ${this.step} ${this.unit}`;
            } else {
                priceMessage += ` - ${window.translations.onetime}: ${this.formatPrice(price.onetimePayment, currency)}`;
            }
        }

        if (['text', 'number', 'textarea', 'slider', 'checkbox', 'radio'].includes(this.type)) {
            const label = document.querySelector(`label[for="${element.id}"]`);
            if (label) {
                label.innerText = `${title} ${priceMessage}`;
            }
        } else if (this.type === 'dropdown') {
            title = element.dataset.title;

            element.innerText = `${title} ${priceMessage}`;
        }
    }

    initEventListener(pricing, updateSummaryCallback) {
        let element;
        switch (this.type) {
            case 'checkbox':
            case 'text':
            case 'number':
            case 'textarea':
            case 'slider':
                element = document.querySelector(`input[name="options[${this.id}]"], textarea[name="options[${this.id}]"]`);
                if (element) {
                    element.addEventListener(this.type === 'slider' ? 'input' : 'change', updateSummaryCallback);
                }
                break;
            case 'radio':
                const radios = document.querySelectorAll(`input[name="options[${this.id}]"]`);
                radios.forEach(radio => {
                    radio.addEventListener('change', updateSummaryCallback);
                });
                break;
            case 'dropdown':
                element = document.querySelector(`select[name="options[${this.id}]"]`);
                if (element) {
                    element.addEventListener('change', updateSummaryCallback);
                }
                break;
            default:
                break;
        }
    }
}

class OptionsManager {
    constructor(pricing, optionsData) {
        this.pricing = pricing;
        this.options = optionsData.map(([id, data]) => new OptionItem(id, data, this.getValue(data.type, id)));
    }

    getValue(type, id) {
        switch (type) {
            case 'checkbox':
                return document.querySelector(`input[name="options[${id}]"]`).checked;
            case 'text':
            case 'number':
            case 'textarea':
                return document.querySelector(`input[name="options[${id}]`)?.value;
            case 'slider':
                return document.querySelector(`input[name="options[${id}]`)?.value;
            case 'radio':
                return document.querySelector(`input[name="options[${id}]"]:checked`)?.value;
            case 'dropdown':
                return document.querySelector(`select[name="options[${id}]`)?.value;
            default:
                return null;
        }
    }

    updateLabels() {
        this.options.forEach(option => {
            let element;
            switch (option.type) {
                case 'checkbox':
                case 'text':
                case 'number':
                case 'textarea':
                case 'slider':
                    element = document.querySelector(`input[name="options[${option.id}]"], textarea[name="options[${option.id}]"]`);
                    break;
                case 'radio':
                    document.querySelectorAll(`input[name="options[${option.id}]"]`).forEach(radio => {
                        option.updateTitleElement(radio, this.pricing, this.pricing.currency);
                    });
                    return;
                case 'dropdown':
                    element = document.querySelector(`select[name="options[${option.id}]"]`);
                    if (element) {
                        Array.from(element.options).forEach(opt => {
                            option.updateTitleElement(opt, this.pricing, this.pricing.currency);
                        });
                    }
                    return;
                default:
                    break;
            }
            if (element) {
                option.updateTitleElement(element, this.pricing, this.pricing.currency);
            }
        });
    }

    recalculateSummary(summary, price) {
        summary.setup += price.setup;
        summary.recurringPayment += price.recurringPayment;
        summary.tax += price.tax;
        summary.onetimePayment = price.onetimePayment;
        summary.subtotal += price.setup + price.recurringPayment + price.onetimePayment;
        return summary
    }

    updateSummary() {
        let summary =  { ...this.pricing };
        this.options.forEach(option => {
            let price;
            let element;
            switch (option.type) {
                case 'checkbox':
                    element = document.querySelector(`input[name="options[${option.id}]"]`);
                    if (element && element.checked) {
                        price = option.getPrice(this.pricing.recurring, this.pricing.currency);
                        summary = this.recalculateSummary(summary, price);
                        this.updateSummaryItem(`options_price[${option.id}]`, price.setup + price.recurringPayment + price.onetimePayment);
                    } else {
                        this.updateSummaryItem(`options_price[${option.id}]`, 0);
                    }
                    option.setValue(element.checked);
                    element.value = element.checked ?' true' : 'false';
                        break;
                    case 'text':
                    case 'number':
                    case 'textarea':
                        element = document.querySelector(`input[name="options[${option.id}]"], textarea[name="options[${option.id}]`);
                        if (element && element.value !== '') {
                            price = option.getPrice(this.pricing.recurring, this.pricing.currency);
                            summary = this.recalculateSummary(summary, price);
                            this.updateSummaryItem(`options_price[${option.id}]`, price.setup + price.recurringPayment + price.onetimePayment);
                            option.setValue(element.value);
                        } else {
                            this.updateSummaryItem(`options_price[${option.id}]`, 0);
                        }
                        break;
                    case 'slider':
                        element = document.querySelector(`input[name="options[${option.id}]"]`);
                        if (element) {
                            price = option.getPrice(this.pricing.recurring, this.pricing.currency, element.value);
                            price = {
                                setup: price.setup * element.value,
                                recurringPayment: price.recurringPayment * element.value,
                                onetimePayment: price.onetimePayment * element.value,
                                tax: price.tax * (element.value / option.step)
                            }
                            summary = this.recalculateSummary(summary, price);

                            this.updateSummaryItem(`options_price[${option.id}]`, price.setup + price.recurringPayment + price.onetimePayment);
                            option.setValue(element.value);
                            const label = element.name.replace('options[', 'options_name[');
                            const labelElement = document.getElementById(label);
                            if (labelElement && labelElement.innerText !== '') {
                                labelElement.innerText = `${option.title} x ${element.value} ${option.unit}`;
                            }
                        }
                        break;
                    case 'radio':
                        document.querySelectorAll(`input[name="options[${option.id}]`).forEach(radio => {
                            if (radio.checked) {
                                price = option.getPrice(this.pricing.recurring, this.pricing.currency, radio.value);
                                summary = this.recalculateSummary(summary, price);
                                this.updateSummaryItem(`options_price[${option.id}]`, price.setup + price.recurringPayment + price.onetimePayment);
                            }
                            option.setValue(radio.value);
                        });
                        break;
                    case 'dropdown':
                        element = document.querySelector(`select[name="options[${option.id}]"]`);
                        if (element) {
                            price = option.getPrice(this.pricing.recurring, this.pricing.currency, element.value);
                            console.log(price)
                            summary = this.recalculateSummary(summary, price);
                            this.updateSummaryItem(`options_price[${option.id}]`, price.setup + price.recurringPayment + price.onetimePayment);
                            option.setValue(element.value);
                        }
                        break;
                default:
                    break;
            }
        });
        this.updateSummaryItem('subtotal', summary.subtotal);
        this.updateSummaryItem('fees', summary.setup);
        this.updateSummaryItem('taxes', summary.tax);
        this.updateSummaryItem('total', summary.subtotal + summary.tax);
        this.updateSummaryItem('onetime', summary.onetimePayment);
        this.updateSummaryItem('recurring', summary.recurringPayment);
        document.querySelector("#currency").value = this.pricing.currency;
    }

    updateSummaryItem(identifier, price) {
        const locale = window.currency === 'EUR' ? 'fr-FR' : 'en-US';
        const formatter = new Intl.NumberFormat(locale, {
            style: 'currency',
            currency: this.pricing.currency
        });
        const element = document.getElementById(identifier);
        if (element) {
            element.innerText = formatter.format(price);
        }

    }
    initOptions() {
        this.options.forEach(option => {
            option.initEventListener(this.pricing, () => {
                this.updateLabels();
                this.updateSummary();
            });
        });
    }
}
document.addEventListener('DOMContentLoaded', function () {
    if (window.optionsPrices === undefined) return;
    const optionsData = Object.entries(window.optionsPrices);

    const radioChecked = document.querySelector("#basket-billing-section input[type=radio]:checked");
    if (radioChecked) {
        const pricing = JSON.parse(radioChecked.dataset.pricing);
        const manager = new OptionsManager(pricing, optionsData);
        manager.updateSummary();
        manager.initOptions();
        manager.updateLabels();
    }
    document.querySelectorAll("#basket-billing-section input[type=radio]").forEach(radio => {
        radio.addEventListener('change', function () {
            const pricing = JSON.parse(radioChecked.dataset.pricing);
            const manager = new OptionsManager(pricing, optionsData);
            manager.pricing = JSON.parse(radio.dataset.pricing);
            manager.updateSummary();
            manager.updateLabels();
        });
    });
});

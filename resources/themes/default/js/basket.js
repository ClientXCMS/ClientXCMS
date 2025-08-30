
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

    getFirstPrice(currency, recurring, value) {
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
        const calculateTax = (taxPercent, price) => Number.parseFloat(price * taxPercent / 100);
        const calculatePrice = (price) => {
            if (price <= 0) {
                return 0;
            }
            const type = window.taxType;
            if (type === 'excluded') {
                return (price);
            } else {
                return (price / (1 + window.taxPercent / 100));
            }
        }
        for (const [key, pricing] of globalPricing) {
            if (!pricing) continue;
            if (pricing.currency === currency || currency === undefined) {
                const recurringSetupKey = `setup_${recurring}`;
                const recurringPayment = recurring === 'onetime' ? 0 : pricing[recurring] ?? null;
                const setup = pricing[recurringSetupKey] ?? null;
                if (recurringPayment === null && recurring !== 'onetime') {
                    return this.getFirstPrice(currency, recurring, value);
                }
                return {
                    setup_ht: Number((calculatePrice(setup / this.step)).toFixed(2)),
                    recurringPayment_ht: Number((calculatePrice(recurringPayment / this.step)).toFixed(2)),
                    tax: Number((calculateTax(window.taxPercent, calculatePrice(setup + recurringPayment + (pricing.onetime ?? 0)))).toFixed(2)),
                    onetimePayment_ht: Number((calculatePrice(pricing.onetime ?? 0) / this.step).toFixed(2)),
                    subtotal: Number((calculatePrice(setup + recurringPayment + (pricing.onetime ?? 0)) / this.step).toFixed(2)),
                }
            }
        }
        return { setup_ht: 0, recurringPayment_ht: 0, tax: 0, onetimePayment_ht: 0, onetimeSetup_ht: 0, subtotal: 0 };
    }

    updateTitleElement(element, pricing, currency) {
        const price = this.getPrice(pricing.recurring, currency, element.value);
        let priceMessage = '';
        let title = this.title;

        if (price.setup_ht > 0) {
            if (this.type === 'slider' && this.step !== 1) {
                priceMessage += `- ${window.translations.setupfee}: ${this.formatPrice(price.setup_ht * this.step, currency)} ${window.per} ${this.step} ${this.unit}`;
            } else {
                priceMessage += `- ${window.translations.setupfee}: ${this.formatPrice(price.setup_ht, currency)}`;
            }
        }
        if (price.recurringPayment_ht > 0) {
            if (this.type === 'slider' && this.step !== 1) {
                priceMessage += `- ${window.translations.recurring}: ${this.formatPrice(price.recurringPayment_ht * this.step, currency)} ${window.per} ${this.step} ${this.unit}`;
            } else {
                priceMessage += ` - ${window.translations.recurring}: ${this.formatPrice(price.recurringPayment_ht, currency)}`;
            }
        }
        if (price.onetimePayment_ht > 0) {
            if (this.type === 'slider' && this.step !== 1) {
                priceMessage += `- ${window.translations.onetime}: ${this.formatPrice(price.onetimePayment_ht * this.step, currency)} ${window.per} ${this.step} ${this.unit}`;
            } else {
                priceMessage += ` - ${window.translations.onetime}: ${this.formatPrice(price.onetimePayment_ht, currency)}`;
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
        summary.setup_ht += price.setup_ht;
        summary.recurringPayment += price.recurringPayment_ht;
        summary.tax += price.tax;
        summary.onetimePayment = price.onetimePayment_ht;
        summary.subtotal += price.subtotal;
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
                        this.updateSummaryItem(`options_price[${option.id}]`, price.subtotal);
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
                            this.updateSummaryItem(`options_price[${option.id}]`, price.subtotal);
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
                                setup_ht: price.setup_ht * element.value,
                                recurringPayment_ht: price.recurringPayment_ht * element.value,
                                onetimePayment_ht: price.onetimePayment_ht * element.value,
                                tax: price.tax * (element.value / option.step),
                            }
                            price.subtotal = price.setup_ht + price.recurringPayment_ht + price.onetimePayment_ht;
                            summary = this.recalculateSummary(summary, price);

                            this.updateSummaryItem(`options_price[${option.id}]`, price.subtotal);
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
                                this.updateSummaryItem(`options_price[${option.id}]`, price.subtotal);
                            }
                            option.setValue(radio.value);
                        });
                        break;
                    case 'dropdown':
                        element = document.querySelector(`select[name="options[${option.id}]"]`);
                        if (element) {
                            price = option.getPrice(this.pricing.recurring, this.pricing.currency, element.value);
                            summary = this.recalculateSummary(summary, price);
                            this.updateSummaryItem(`options_price[${option.id}]`, price.subtotal);
                            option.setValue(element.value);
                        }
                        break;
                default:
                    break;
            }
        });
        this.updateSummaryItem('subtotal', summary.subtotal);
        this.updateSummaryItem('fees', summary.setup_ht);
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

function updateSummaryItem(identifier, price, currency) {
    const locale = window.currency == 'EUR' ? 'fr-FR' : 'en-US';
    const formatter = new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: currency
    });
    const element = document.getElementById(identifier);
    if (element) {
        element.innerText = formatter.format(price);
    }
}

function updateSummary(amount,  currency, currentBalance) {
    const total = amount;
    updateSummaryItem('amount', amount, currency);
    updateSummaryItem('total', total, currency);
    if (currentBalance !== undefined) {
        updateSummaryItem('balance', currentBalance + amount, currency);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const input = document.querySelector("input[name='amount']");
    const amountElement = document.getElementById('amount');
    const currency = amountElement.getAttribute('data-currency');
    const currentBalance = parseFloat(amountElement.getAttribute('data-balance')) || 0;
    const amount = parseFloat(input.value);
    updateSummary(amount,  currency, currentBalance);
    input.addEventListener('input', function () {
        const amount = parseFloat(input.value);
        updateSummary(amount, currency, currentBalance);
    });
})

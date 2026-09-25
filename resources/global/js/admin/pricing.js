const showmorepricingbtn = document.getElementById('showmorepricingbtn');
const calculatorBtn = document.getElementById('calculatorBtn');
const table = document.getElementById('pricingtable');
const collapsiblePricing = table ? Array.from(table.querySelectorAll('.hidden')) : [];

if (table && showmorepricingbtn) {
    showmorepricingbtn.addEventListener('click', function (e) {
        e.preventDefault();
        collapsiblePricing.forEach((el) => el.classList.toggle('hidden'));
        showmorepricingbtn.setAttribute('aria-expanded', showmorepricingbtn_hidden() ? 'false' : 'true');
    });
}

function showmorepricingbtn_hidden() {
    return collapsiblePricing.some((el) => el.classList.contains('hidden'));
}
if (calculatorBtn) {
    calculatorBtn.addEventListener('click', function (e) {
        e.preventDefault();
        const percentage = document.querySelector('input[name="percentage"]').value;
        const monthlyPrice = document.querySelector('input[data-months="1"][name$="[price]"]').value;
        const monthlySetup = document.querySelector('input[data-months="1"][name$="[setup]"]').value;

        const prices = document.querySelectorAll('input[name^="pricing"]:not([name*="onetime"])');
        if (percentage > 100 || percentage < 0 || percentage === '') {
            return;
        }
        prices.forEach((price) => {
            const months = price.getAttribute('data-months');
            const setup = document.querySelector('input[data-months="' + months +'"][name$="[setup]"]');
            if (months === '1' || months === '0.5') return;
            if (months === '24' || months === '36' && showmorepricingbtn_hidden()) return;
            if (price.value === '' || setup.value === '') {
                if (price.value === '') {
                    price.value = 0;
                }
                if (setup.value === '') {
                    setup.value = '';
                }
            }

            const newPrice = monthlyPrice * months - (monthlyPrice * months) *(percentage / 100);
            const newSetup = monthlySetup * months -  (monthlySetup * months) * (percentage / 100);
            price.value = newPrice.toFixed(2);
            setup.value = newSetup.toFixed(2);
        });
    });
}

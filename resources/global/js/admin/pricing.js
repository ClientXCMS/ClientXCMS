const showmorepricingbtn = document.getElementById('showmorepricingbtn');
const calculatorBtn = document.getElementById('calculatorBtn');
const table = document.getElementById('pricingtable');
if (!table){
    const hidden = table.querySelectorAll('.hidden');
} else {
    const hidden = [];
}
showmorepricingbtn.addEventListener('click', function (e) {
    e.preventDefault();
    Array.from(hidden).map((el) => el.classList.toggle('hidden'));
});

function showmorepricingbtn_hidden() {
    const filter = Array.from(hidden).filter((el) => el.classList.contains('hidden'));
    if (filter.length > 0) {
        return true;
    }
    return false;
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

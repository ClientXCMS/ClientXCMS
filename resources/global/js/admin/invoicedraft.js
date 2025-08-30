import {HSOverlay} from "preline";

const addItemBtn = document.getElementById('add-item-btn');
if (addItemBtn) {
    addItemBtn.addEventListener('click', function () {
        const selectedValue = document.getElementById('product').value;
        const parts = selectedValue.split('-');
        const related = parts[0];
        const relatedId = parts[1];
        addItemBtn.ariaDisabled = true;
        addItemBtn.setAttribute('disabled', 'disabled');
        fetch(addItemBtn.dataset.fetch + '?related_id=' + relatedId + '&related='  + related).then(response => {
            if (!response.ok) {
                Swal.fire({
                    text: 'Cannot add item to invoice. Status code : ' + response.status,
                    icon: 'danger',
                })
                return '';
            }
            return response.text();
        }).then(data => {
            const contentElement = document.getElementById('item-content');
            if (!contentElement) {
                console.error('Content element not found');
                return;
            }
            if (data.length === 0) {
                console.error('No data received');
                return;
            }
            contentElement.innerHTML = data;
            HSOverlay.open(document.getElementById('btn-draftitem'));
            tryBillingSection();
        }).catch(error => {
            console.error('Error:', error);
            alert('Error: ' + error.message)
        }).finally(() => {
            addItemBtn.ariaDisabled = false;
            addItemBtn.removeAttribute('disabled');
        })
    });
    tryBillingSection();

    function tryBillingSection() {
        const checkboxes = document.querySelectorAll(".basket-billing-section input[type=radio]");
        console.log(checkboxes);
        checkboxes.forEach(function (checkbox) {
            if (checkbox.checked) {
                const pricing = JSON.parse(checkbox.dataset.pricing);
                updateSummary(pricing, checkbox.form);
            }
            checkbox.addEventListener('change', function () {
                const pricing = JSON.parse(checkbox.dataset.pricing);
                updateSummary(pricing, checkbox.form);
            });
        });
    }

    function updateSummary(pricing, form) {
        const unitPriceTTC = form.querySelector('input[name="unit_price_ttc"]');
        const unitSetupTTC = form.querySelector('input[name="unit_setup_ttc"]');
        unitSetupTTC.value = pricing.setup_ttc;
        unitPriceTTC.value = pricing.price_ttc;
    }
}

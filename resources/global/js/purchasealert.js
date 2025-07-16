
const productName = document.getElementById('purchasealert-productname');
const hour = document.getElementById('purchasealert-hour');
const purchaseButton = document.getElementById('purchasealert-button');
const alertBox = document.getElementById('purchasealert');
const closeButton = document.getElementById('purchasealert-close');

function getLastPurchase() {
    if (localStorage.getItem('purchasealert') === 'closed') {
        return;
    }
    fetch(window.purchasealert.url)
        .then(response => response.json())
        .then(data => {
            if (data.message) {
                return;
            }
            productName.innerText = data.data.product_name;
            productName.href = data.data.group_link;
            hour.innerText = data.data.hours;
            purchaseButton.href = data.data.link;
            alertBox.classList.remove('hidden', 'slide-out');
            alertBox.classList.add('slide-in');
            console.log(alertBox)
            setTimeout(() => {
                alertBox.classList.add('slide-out');
                setTimeout(() => alertBox.classList.add('hidden'), 500);
            }, window.purchasealert.timeout);
        });
}

closeButton.addEventListener('click', function() {
    alertBox.classList.add('slide-out');
    setTimeout(() => alertBox.classList.add('hidden'), 500);
    localStorage.setItem('purchasealert', 'closed');
});

getLastPurchase();
setInterval(getLastPurchase, window.purchasealert.interval);

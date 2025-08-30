// select this : data-hs-remove-element="#cookie-banner"
const cookieBanner = document.querySelector('#cookie-btn');
if (cookieBanner) {
    cookieBanner.addEventListener('click', (e) => {
        e.preventDefault();
        fetch(cookieBanner.dataset.url, {
            headers: {
                'Content-Type': 'application/json'
            }
        });
    });
}

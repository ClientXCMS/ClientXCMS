import {HSOverlay} from "preline";

document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function () {
        if (window.location.hash === '#renewals') {
            HSOverlay.open(document.querySelector('#renewal-button'));
        }
    }, 100);
});

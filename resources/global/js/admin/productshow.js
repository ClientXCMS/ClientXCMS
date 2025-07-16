import {HSOverlay} from "preline";

document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function () {
        if (window.location.hash === '#config') {
            HSOverlay.open(document.querySelector('#btn-config'));
        }
    }, 100);
});

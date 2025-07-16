import {HSOverlay} from "preline";

const hash = window.location.hash;

if (hash.includes('#')) {
    const id = hash.split('-')[1];
    console.log(document.querySelector("#btn-" + id))

    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(function () {
            HSOverlay.open(document.querySelector("#btn-" + id))
        }, 100);
    })
}

document.querySelectorAll('.filter-checkbox').forEach(function (el) {
    el.addEventListener('change', function () {
        let redirect = el.hasAttribute('data-redirect') ? el.getAttribute('data-redirect') : location.pathname;
        if (this.value === 'all'){
            location.href = redirect
            return;
        }

        if (this.checked){
            location.href = redirect + '?filter=' + this.value;
        } else {
            location.href = redirect;
        }
    });
});

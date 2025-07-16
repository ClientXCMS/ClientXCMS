import './bootstrap';
import './darkmode.js'
import '@grafikart/drop-files-element'
import 'preline'
import.meta.glob([
    '/resources/global/**',
    '/resources/global/js/**',
    '/resources/themes/*/screenshot.png'
]);
const logoutBtn = document.querySelector('#logout-btn');
if (logoutBtn) {
    logoutBtn.addEventListener('click', (e) => {
        e.preventDefault();
        document.getElementById('logout-form').submit();
    });
}

function generate_password(button) {
    const length = 12;
    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789$@!%*?&";
    let password = "";
    const form = button.closest('form');
    for (let i = 0, n = charset.length; i < length; ++i) {
        password += charset.charAt(Math.floor(Math.random() * n));
    }
    const inputs = form.querySelectorAll('.input-password');
    inputs.forEach((input) => {
        input.value = password;
        const button = input.closest('div').querySelector('button');
        if (button && input.type === 'password') {
            button.click();
        }
    });
}
const passwordBtn = document.querySelectorAll('.generate-password-btn');
passwordBtn.forEach((button) => {
    button.addEventListener('click', (e) => {
        e.preventDefault();
        generate_password(button);
    });
});

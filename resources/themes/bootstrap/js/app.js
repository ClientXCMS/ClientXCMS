import './darkmode.js'
import '@grafikart/drop-files-element'
// used externally
import.meta.glob([
    '/resources/global/**',
    '/resources/global/js/**',
]);
import 'bootstrap'
const logoutBtn = document.querySelector('#logout-btn');
if (logoutBtn) {
    logoutBtn.addEventListener('click', (e) => {
        e.preventDefault();
        document.getElementById('logout-form').submit();
    });
}

function toggle_password_visibility(button) {
    const input = button.closest('div').querySelector('.input-password');
    if (input.type == 'password') {
        input.type = 'text';
        const icon = button.querySelector('i');
        icon.classList.remove('bi-eye-slash-fill');
        icon.classList.add('bi-eye-fill');
    } else {
        input.type = 'password';
        const icon = button.querySelector('i');
        icon.classList.remove('bi-eye-fill');
        icon.classList.add('bi-eye-slash-fill');
    }
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
        if (button) {
            toggle_password_visibility(button)
        }
    });
}
const passwordBtn = document.querySelectorAll('.generate-password-btn');
passwordBtn.forEach((button) => {
    button.addEventListener('click', (e) => {
        e.preventDefault();
        generate_password(button);

    });
    const btn = button.closest('div').closest('div').querySelector('button');
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        toggle_password_visibility(btn);
    });
});

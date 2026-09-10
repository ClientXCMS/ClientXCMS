import { Passkeys, UserCancelledError } from '@laravel/passkeys';

const redirectOnSuccess = (response) => {
    if (response?.redirect) window.location.assign(response.redirect);
};

const showError = (container, message) => {
    const target = container.querySelector('[data-passkey-error]');
    if (target) {
        target.textContent = message;
        target.hidden = false;
    }
};

const wasCancelled = (error) => error instanceof UserCancelledError;

const setLoading = (button, loading) => {
    if (!button) return;
    button.disabled = loading;
    button.setAttribute('aria-busy', String(loading));
    const spinner = button.querySelector('[data-passkey-spinner]');
    if (spinner) spinner.hidden = !loading;
};

document.addEventListener('DOMContentLoaded', async () => {
    document.querySelectorAll('[data-passkey-login]').forEach(async (container) => {
        if (!Passkeys.isSupported()) {
            container.hidden = true;
            return;
        }

        const button = container.querySelector('[data-passkey-login-button]');
        const remember = () => Boolean(document.querySelector('input[name="remember"]')?.checked);
        const options = { remember };

        button?.addEventListener('click', async () => {
            setLoading(button, true);
            try {
                redirectOnSuccess(await Passkeys.verify(options));
            } catch (error) {
                if (wasCancelled(error)) return;
                showError(container, container.dataset.errorMessage);
            } finally {
                setLoading(button, false);
            }
        });

        try {
            redirectOnSuccess(await Passkeys.autofill(options));
        } catch (error) {
            if (wasCancelled(error)) return;
            showError(container, container.dataset.errorMessage);
        }
    });

    document.querySelectorAll('[data-passkey-confirm]').forEach((container) => {
        const button = container.querySelector('[data-passkey-confirm-button]');
        if (!Passkeys.isSupported()) {
            container.hidden = true;
            return;
        }
        button?.addEventListener('click', async () => {
            setLoading(button, true);
            try {
                redirectOnSuccess(await Passkeys.verify({ routes: {
                    options: '/passkeys/confirm/options', submit: '/passkeys/confirm',
                }}));
            } catch (error) {
                if (wasCancelled(error)) return;
                showError(container, container.dataset.errorMessage);
            } finally {
                setLoading(button, false);
            }
        });
    });

    document.querySelectorAll('[data-passkey-register]').forEach((container) => {
        const button = container.querySelector('[data-passkey-register-button]');
        if (!Passkeys.isSupported()) {
            button?.setAttribute('disabled', 'disabled');
            return;
        }
        button?.addEventListener('click', async () => {
            const name = container.querySelector('[data-passkey-name]')?.value.trim();
            if (!name) return showError(container, container.dataset.nameRequired);
            button.disabled = true;
            try {
                await Passkeys.register({ name, routes: {
                    options: '/client/profile/passkeys/options', submit: '/client/profile/passkeys',
                }});
                window.location.reload();
            } catch (error) {
                if (wasCancelled(error)) return;
                if (error?.message === 'Password confirmation required.') {
                    window.location.assign('/confirm-password');
                    return;
                }
                showError(container, container.dataset.errorMessage);
            } finally {
                button.disabled = false;
            }
        });
    });
});

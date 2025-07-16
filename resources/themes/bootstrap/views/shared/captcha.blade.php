<?php
/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Year: 2025
 */
?>
?>
?>
@php($dark = is_darkmode())
@if(! isset($dark))
        <script>
            const currentTheme = document.body.classList.contains('dark') === true ? 'dark' : 'light';

            document.querySelectorAll('[data-theme]').forEach((element) => {
                element.setAttribute('data-theme', currentTheme ? currentTheme : 'light')
            })
        </script>
@endif

@if(setting('captcha_driver') === 'recaptcha')
        <script src="https://www.recaptcha.net/recaptcha/api.js?hl={{ app()->getLocale() }}" async defer></script>
        <script>
            const captchaForm = document.getElementById('captcha-form');

            function submitCaptchaForm() {
                captchaForm.submit();
            }

            if (captchaForm) {
                captchaForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    grecaptcha.execute();
                });
            }
        </script>

    <div class="g-recaptcha" data-sitekey="{{ setting('captcha_site_key') }}" data-callback="submitCaptchaForm" data-size="invisible"></div>

@elseif(setting('captcha_driver') === 'hcaptcha')
        <script src="https://hcaptcha.com/1/api.js?hl={{ app()->getLocale() }}" async defer></script>
        <script>
            const captchaForm = document.getElementById('captcha-form');

            if (captchaForm) {
                captchaForm.addEventListener('submit', function (e) {
                    const hCaptchaInput = captchaForm.querySelector('[name="h-captcha-response"]');

                    if (hCaptchaInput && hCaptchaInput.value === '') {
                        e.preventDefault();

                        hcaptcha.execute();
                    }
                });
            }
        </script>

    <div class="h-captcha mb-2 @if($center ?? true) text-center @endif" data-sitekey="{{ setting('captcha_site_key') }}" data-theme="{{ ($dark ?? false) ? 'dark' : 'light' }}"></div>
@elseif(setting('captcha_driver') === 'cloudflare')
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

    <div class="cf-turnstile mb-2 @if($center ?? true) text-center @endif" data-sitekey="{{ setting('captcha_site_key') }}" data-theme="{{ ($dark ?? false) ? 'dark' : 'light' }}"></div>
@endif

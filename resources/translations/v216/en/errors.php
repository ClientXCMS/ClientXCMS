<?php

/*
 * v2.16 — Translations for the redesigned error pages.
 * See resources/translations/v216/README.md for the rationale.
 *
 * Key convention: errors.{status_code}.{slot}
 */

return [
    'common' => [
        'home' => 'Back to home',
        'dashboard' => 'Back to dashboard',
        'support' => 'Contact support',
        'previous' => 'Go back',
        'status_code' => 'Error :code',
    ],

    '401' => [
        'title' => 'You are not signed in',
        'heading' => 'Authentication required',
        'description' => 'This page is only available to signed-in users. Please sign in and try again.',
        'sign_in' => 'Sign in',
    ],
    '403' => [
        'title' => 'Access denied',
        'heading' => 'Forbidden',
        'description' => 'You do not have permission to access this resource. If you think this is a mistake, contact support.',
    ],
    '404' => [
        'title' => 'Page not found',
        'heading' => 'We looked everywhere…',
        'description' => 'The page you are looking for has moved or never existed. Use one of the buttons below to get back on track.',
    ],
    '419' => [
        'title' => 'Your session expired',
        'heading' => 'Session expired',
        'description' => 'For your safety we ended your session after a period of inactivity. Please reload the page and submit the form again.',
        'reload' => 'Reload the page',
    ],
    '422' => [
        'title' => 'Invalid request',
        'heading' => 'Something does not look right',
        'description' => 'We could not process your request because some of the data was missing or invalid. Go back and try again.',
    ],
    '429' => [
        'title' => 'Too many requests',
        'heading' => 'Slow down',
        'description' => 'You have made too many requests in a short period. Please wait a moment before trying again.',
    ],
    '500' => [
        'title' => 'Something went wrong on our side',
        'heading' => 'Internal server error',
        'description' => 'An unexpected error has been logged. Our team has been notified. Please try again in a few minutes.',
    ],
    '503' => [
        'title' => 'We are doing some maintenance',
        'heading' => 'Service unavailable',
        'description' => 'We are upgrading the platform. The service will be back online shortly. Thank you for your patience.',
    ],
];

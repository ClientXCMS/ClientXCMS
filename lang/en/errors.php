<?php

return [
    'common' => [
        'home' => 'Back to home',
        'dashboard' => 'Return to dashboard',
        'support' => 'Contact support',
        'previous' => 'Go back',
        'status_code' => 'Error :code',
    ],
    401 => [
        'title' => 'You are not connected',
        'heading' => 'Authentication required',
        'description' => 'This page is reserved for logged in users. Please log in and try again.',
        'sign_in' => 'Log in',
    ],
    403 => [
        'title' => 'Access denied',
        'heading' => 'Forbidden',
        'description' => 'You do not have permission to access this resource. If you think this is an error, contact support.',
    ],
    404 => [
        'title' => 'Page not found',
        'heading' => 'We searched everywhere...',
        'description' => 'Sorry, the page you are looking for could not be found.',
        'back' => 'Return to previous page',
        'home' => 'Go to the homepage',
        'dashboard' => 'Go to the dashboard',
    ],
    419 => [
        'title' => 'Your session has expired',
        'heading' => 'Session expired',
        'description' => 'For your security, we have closed your session after a period of inactivity. Please reload the page and submit the form again.',
        'reload' => 'Reload page',
    ],
    422 => [
        'title' => 'Invalid request',
        'heading' => 'something is wrong',
        'description' => 'We were unable to process your request because some data is missing or incorrect. Go back and try again.',
    ],
    429 => [
        'title' => 'Too many requests',
        'heading' => 'Gently…',
        'description' => 'You have made too many requests in a short time. Please wait a moment before trying again.',
    ],
    500 => [
        'title' => 'Internal Server Error',
        'heading' => 'Internal Server Error',
        'description' => 'Sorry, an internal error occurred.',
        'back' => 'Return to previous page',
        'home' => 'Go to the homepage',
    ],
    503 => [
        'title' => 'Maintenance in progress',
        'heading' => 'Service unavailable',
        'description' => 'We are updating the platform. The service will be back online shortly. Thank you for your patience.',
    ],
];

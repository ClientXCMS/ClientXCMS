<?php

return [
    'install' => 'Installation',
    'step' => 'Stage',
    'settings' => [
        'title' => 'Parameters',
        'client_id' => 'Client ID',
        'client_secret' => 'Client Secret',
        'hosting_name' => 'Company Name',
        'connect' => 'Sign in',
        'migrationwarning' => 'Warning: the database has not been migrated. Please run "php artisan migrate --force" to migrate the database.',
        'detecteddomain' => 'Domain detected: :domain. Make sure that the domain exactly matches the domain of your license (including the subdomain if applicable).',
        'locales' => 'Languages',
        'infolicense' => 'To obtain a client ID and a client secret, you must have a CLIENTXCMS license. <a href=":link" target="_blank" class="underline">Click here to retrieve your credentials for free.</a>',
        'eula' => 'By consulting this page, you accept the terms of the CLIENTXCMS license available at clientxcms.com/eula.',
    ],
    'register' => [
        'title' => 'Registration',
        'btn' => 'Create Account',
        'telemetry' => 'Send anonymized telemetry data. This helps us improve the CMS and fix bugs. You can disable this option later in the settings.',
    ],
    'summary' => [
        'title' => 'Summary',
        'btn' => 'Finish',
    ],
    'submit' => 'Send',
    'password' => 'Password',
    'firstname' => 'First name',
    'lastname' => 'Last name',
    'email' => 'E-mail',
    'password_confirmation' => 'Password confirmation',
    'authentication' => 'Authentication',
    'extensions' => 'Extensions',
    'departmentsseeder' => [
        'general' => [
            'name' => 'General',
            'description' => 'General Department',
        ],
        'billing' => [
            'name' => 'Invoicing',
            'description' => 'Billing Department',
        ],
        'technical' => [
            'name' => 'Technique',
            'description' => 'Technical Department',
        ],
        'sales' => [
            'name' => 'Commercial',
            'description' => 'Sales Department',
        ],
    ],
];

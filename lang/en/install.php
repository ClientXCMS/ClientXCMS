<?php

return [
    'install' => 'Installation',
    'step' => 'Step',
    'settings' => [
        'title' => 'Settings',
        'client_id' => 'Client ID',
        'client_secret' => 'Client Secret',
        'hosting_name' => 'Company Name',
        'connect' => 'Connect',
        'locales' => 'Languages',
        'infolicense' => 'To obtain a client ID and client secret, you must have a CLIENTXCMS license. <a href=":link" target="_blank" class="underline">Click here to get your credentials for free.</a>',
        'migrationwarning' => 'Warning: the database has not been migrated. Please run "php artisan migrate --force --seed" to migrate the database.',
        'detecteddomain' => 'Detected domain: :domain. Make sure the domain exactly matches the one on your license (including subdomain if applicable).',
        'eula' => 'By viewing this page, you agree to the terms of the CLIENTXCMS license available at clientxcms.com/eula.',
    ],
    'register' => [
        'title' => 'Registration',
        'btn' => 'Create account',
        'telemetry' => 'Send anonymized telemetry data. This helps us improve the CMS and fix bugs. You can disable this option later in the settings.',
    ],
    'summary' => [
        'title' => 'Summary',
        'btn' => 'Finish',
    ],
    'submit' => 'Submit',
    'password' => 'Password',
    'firstname' => 'First name',
    'lastname' => 'Last name',
    'email' => 'Email',
    'password_confirmation' => 'Password confirmation',
    'authentication' => 'Authentication',
    'extensions' => 'Extensions',
    'departmentsseeder' => [
        'general' => [
            'name' => 'General',
            'description' => 'General department',
        ],
        'billing' => [
            'name' => 'Billing',
            'description' => 'Billing department',
        ],
        'technical' => [
            'name' => 'Technical',
            'description' => 'Technical department',
        ],
        'sales' => [
            'name' => 'Sales',
            'description' => 'Sales department',
        ],
    ],
    'security_questions' => [
        'pet_name' => 'What is the name of your first pet?',
        'birth_city' => 'In which city were you born?',
        'mother_maiden_name' => 'What is your mother\'s maiden name?',
        'first_school' => 'What is the name of your first school?',
        'favorite_movie' => 'What is your favorite movie?',
        'childhood_nickname' => 'What was your childhood nickname?',
    ],
];

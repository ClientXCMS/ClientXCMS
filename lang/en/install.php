<?php

return [
  'install' => 'Installation',
  'step' => 'Step',
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
    'favorite_movie' => 'What is your favourite film?',
    'childhood_nickname' => 'What was your childhood nickname?',
  ],
];

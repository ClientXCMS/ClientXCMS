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
];

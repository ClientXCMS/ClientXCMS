<?php

return [
    'product' => [
        'no_data' => 'No additional information is required when ordering.',
    ],
    'config' => [
        'server' => 'Mailcow Server',
        'max_domains' => 'Maximum domains',
        'max_mailboxes' => 'Maximum boxes per domain',
        'max_aliases' => 'Maximum aliases per domain',
        'default_quota_mib' => 'Default quota of a box (MiB)',
        'max_quota_mib' => 'Maximum quota of a box (MiB)',
        'domain_quota_mib' => 'Total domain quota (MiB)',
        'rate_limit_value' => 'Sending limit value',
        'rate_limit_frame' => 'Sending limit period',
        'backup_mx' => 'Backup MX Server',
        'relay_all_recipients' => 'Relay all recipients',
        'seconds' => 'Seconds',
        'minutes' => 'Minutes',
        'hours' => 'Hours',
    ],
    'panel' => [
        'title' => 'Email domains',
        'permission' => 'Manage Mailcow domains',
        'usage' => ':used domain(s) used on :max',
        'empty' => 'No domains are configured yet.',
        'mailboxes' => 'Boxes',
        'aliases' => 'Alias',
        'admin' => 'Administrator',
        'password' => 'Password',
        'status_active' => 'Active',
        'status_suspended' => 'Suspended',
    ],
    'actions' => [
        'add' => 'Add the domain',
        'delete' => 'DELETE',
        'delete_confirm' => 'Permanently delete this domain and all its email data?',
        'dns' => 'Export DNS',
        'domain_admin' => 'Mailcow Administration',
    ],
    'messages' => [
        'domain_created' => 'The Mailcow domain has been created.',
        'domain_deleted' => 'The Mailcow domain has been deleted.',
    ],
    'errors' => [
        'domain_exists' => 'This domain is already provisioned.',
        'domain_limit' => 'The domain limit of this service has been reached.',
        'config_missing' => 'Mailcow product configuration could not be found.',
        'api' => 'Mailcow was unable to process the request.',
    ],
];

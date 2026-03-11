<?php

return [
    'services' => [
        'lifecycle_title' => 'Expiration & suspension lifecycle',
        'suspend_after_unpaid_days' => 'Automatic suspension after unpaid invoice (D+)',
        'renewal_grace_days' => 'Renewal grace period with reminders (days)',
        'late_fee_until_days' => 'Late-fee renewal window until (D+)',
        'expire_delete_after_days' => 'Final expiration / data deletion (D+)',
    ],
    'extensions' => [
        'import_lock' => 'An import is already running. Please retry shortly.',
        'checksum_invalid' => 'Invalid SHA-256 checksum.',
        'zip_too_many_files' => 'ZIP archive too large (too many files).',
        'zip_too_large_uncompressed' => 'ZIP archive is too large once extracted.',
        'zip_invalid_single_folder' => 'ZIP must contain exactly one extension folder.',
    ],
];

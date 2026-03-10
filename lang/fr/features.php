<?php

return [
    'services' => [
        'lifecycle_title' => 'Cycle d\'expiration et de suspension',
        'suspend_after_unpaid_days' => 'Suspension automatique après facture impayée (J+)',
        'renewal_grace_days' => 'Fenêtre de renouvellement avec rappels (jours)',
        'late_fee_until_days' => 'Renouvellement avec frais de retard jusqu\'à (J+)',
        'expire_delete_after_days' => 'Expiration finale / suppression des données (J+)',
    ],
    'extensions' => [
        'import_lock' => 'Un import est déjà en cours. Veuillez réessayer.',
        'checksum_invalid' => 'Checksum SHA-256 invalide.',
        'zip_too_many_files' => 'Archive ZIP trop volumineuse (trop de fichiers).',
        'zip_too_large_uncompressed' => 'Archive ZIP trop lourde après décompression.',
        'zip_invalid_single_folder' => 'Le ZIP doit contenir un unique dossier d\'extension.',
    ],
];

<?php

return [
    'admin' => [
        'settings' => [
            'title' => 'Helpdesk Bridge',
            'description' => 'Bridge webhook des emails entrants pour les tickets support',
            'success' => 'Paramètres du Helpdesk Bridge mis à jour.',
            'fields' => [
                'reply_mailbox' => 'Boîte mail de réponse (local-part)',
                'reply_mailbox_help' => 'Exemple : support => support+uuid.signature@votre-domaine.tld',
                'webhook_token' => 'Token webhook inbound',
                'webhook_token_help' => 'Utiliser ce token dans le header X-Helpdesk-Webhook-Token.',
                'create_ticket_from_inbound' => 'Créer un ticket sur premier email entrant d\'un client existant',
            ],
        ],
    ],
    'inbound' => [
        'default_subject' => 'Demande de support par email',
    ],
];

<?php

return [
    'admin' => [
        'settings' => [
            'title' => 'Helpdesk Bridge',
            'description' => 'Inbound email webhook bridge for helpdesk tickets',
            'success' => 'Helpdesk bridge settings updated.',
            'fields' => [
                'reply_mailbox' => 'Reply mailbox local-part',
                'reply_mailbox_help' => 'Example: support => support+uuid.signature@your-domain.tld',
                'webhook_token' => 'Inbound webhook token',
                'webhook_token_help' => 'Use this token in X-Helpdesk-Webhook-Token header.',
                'create_ticket_from_inbound' => 'Create a ticket for first inbound email from an existing customer',
            ],
        ],
    ],
    'inbound' => [
        'default_subject' => 'Support request by email',
    ],
];

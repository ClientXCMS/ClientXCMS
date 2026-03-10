# Helpdesk Bridge Addon

Addon UUID: `helpdesk-bridge`

## Translation directory

`addons/helpdesk-bridge/lang/{locale}/helpdesk_bridge.php`

## Features

- Inbound email webhook endpoint: `POST /api/client/webhooks/helpdesk/inbound-email`
- Signed reply address parsing (`localpart+uuid.signature@domain`)
- Reply-to-existing-ticket flow with sender/signature verification
- Optional first inbound email -> new ticket creation for existing customer
- Admin settings page under Helpdesk card

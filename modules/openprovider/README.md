# Openprovider

Openprovider domain registrar and DNS integration for ClientXCMS.

Create an active domain server with hostname `openprovider`, then set the Openprovider API username and password. The address may be left empty to use `https://api.openprovider.eu`, or set to a compatible custom API endpoint. An optional `ip` value can be supplied when the Openprovider account restricts authentication to an IP address.

The module supports availability checks, contact and domain creation, renewal, domain information, nameserver updates, and DNS record management. Transfers, auth-code operations, DNSSEC, premium fee acceptance, and registry-specific additional data are not included in version 1.0.0.

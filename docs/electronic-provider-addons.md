# Electronic invoicing provider addons

Each regulatory platform is shipped as a separate addon. The core owns Factur-X rendering, idempotency, queues, retries, reconciliation and the administration dashboard; an addon only adapts the provider API.

## Minimum implementation

1. Implement `ElectronicExchangeProviderInterface` with a stable `key()`.
2. Declare only verified capabilities: `b2b`, `b2g`, `transaction_reporting`, `payment_reporting`, and `status_tracking`.
3. Register a singleton in `ElectronicProviderRegistry` from the addon service provider.
4. Keep credentials encrypted, expose a connection diagnostic, and never include credentials in logs or API responses.
5. Convert provider responses to `ProviderSubmissionResult` and `ProviderStatusResult`; keep the raw non-secret response for support diagnostics.

Providers must not create their own submission jobs, transmission tables or generic document dashboards. Webhooks may be provider-specific, but must update the central `ElectronicDocument` and create idempotent `ElectronicDocumentEvent` records.

Do not publish a connector until the provider's official contract and test environment have been validated. A configurable list of guessed endpoints is not considered a supported connector.

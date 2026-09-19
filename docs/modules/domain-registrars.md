# Domain registrar extensions

Extend `App\Abstracts\AbstractDomainRegistrar`. Implement `uuid`, `title`,
`testConnection`, `checkAvailability`, `register` and `renew`. Optional operations
fail explicitly by default. New optional methods added to DomainRegistrarInterface
must also have a default in the abstract class. Signature changes remain breaking;
modules implementing the interface directly must implement any new methods.

Optional capabilities:

* `DomainCatalogInterface::catalogPage(Server, offset, limit)` returns a
  `DomainCatalogPage` containing standard reseller cost totals for explicit
  action/billing/currency combinations, the next offset and optional total.
  No premium quotes, synthetic zero prices or unsupported duration extrapolation.
* `DomainDnsInitializationInterface` advertises managed nameservers and record
  types. `initializationRecords` must ensure a managed zone exists and return the
  complete zone or throw. Never hide API errors behind an empty result.

OpenProvider uses GET `/v1beta/tlds` with `with_price=true`, pagination and standard
reseller prices. This endpoint does not supply explicit multi-year quotes: this
adapter imports one-year costs only. Configure additional durations manually.
The managed nameservers are ns1.openprovider.nl, ns2.openprovider.be and
ns3.openprovider.eu. Private/vanity DNS servers are intentionally not inferred.

## Lifecycle

Apply the migration before deploying the new forms. TLD defaults initially remain
empty and DNS initialization disabled; existing services are not migrated.
Configure nameservers on sellable TLDs. New basket rows snapshot DNS defaults and
the selected server; old rows retain their stored nameservers.

Registration success is separate from DNS success. An existing registrar ID
prevents a second registration. Initialization checks remote active status and
nameservers, reads the complete zone, adds missing records, and fails on conflicts
without deleting or overwriting existing records. Retry from the admin service page.
Pending registrations can be retried after the registrar confirms activation.

## Catalog and copy

Permission: `admin.manage_domain_tlds`. Preview operations expire after 30 minutes;
catalog snapshots after one hour. Catalogs are isolated by admin, server and a keyed
fingerprint including credentials/environment. No credentials are stored in previews.
Run `php artisan queue:work` for an asynchronous default queue. The sync queue also
works for local development but executes catalog loading during the request.
A failed catalog may be reloaded from the tools page. No periodic price sync occurs.

Prices use the configured store currency. Conversion is explicit; the formula is
`round(cost * exchange_rate * (1 + markup_percent / 100) + fixed_amount, 2)`.
The administrator can edit calculated totals. Existing TLDs are skipped unless
selected for update, and their DNS and status are preserved. Prices are patched by
currency/action/billing; unsubmitted currencies, setup fees and periods survive.
A submitted blank price disables only that cell. Copying prices overlays source
cells and preserves destination cells absent in the source.

Apply operations are transactional, one-shot and reject stale destination or
connection state. Their IDs/counts are logged in ActionLog. No remote registration
or registrar contract is submitted by catalog import or config copying.

# Upgrading to ClientXCMS v2.16

This document captures everything that changed in v2.16 from an
operator's standpoint: what's new, what's safe to skip, what to run,
what to roll back if needed.

> **TL;DR** — Pull the new code, run `php artisan clientxcms:upgrade`,
> done. Every migration is additive; every BC layer is preserved.

## Compatibility

| Component  | v2.15 minimum | v2.16 minimum |
|---|---|---|
| PHP        | 8.3           | 8.3            |
| MySQL      | 8.0           | 8.0 (5.7 OK for the legacy index path) |
| MariaDB    | 10.4          | 10.4           |
| Laravel    | 12.x          | 12.x           |
| Node       | 20.x          | 20.x           |

No PHP version bump. No removed PHP extension. The `intl` and `gd`
requirements are unchanged.

## What's new in v2.16

| Area | Headline | PR |
|---|---|---|
| Billing | Renewal invoices can no longer be duplicated by a customer spamming the *Renew* button. Atomic invoice numbering replaces the race-prone `count()+1`. PDFs ship a SHA-256 hash for audit. Credit notes ("avoirs") with their own counter. | #1, #13 |
| Billing | Pay-as-you-go pricing. `php artisan usage:bill-cycle` issues a pending invoice on the 1st of the month based on the peak of each metered resource. | #14 |
| Admin UX | Empty CTA button no longer shipped in manual emails when the URL field is left blank. Admin forms no longer silently overwrite typed input with the model value after a validation error. | #2 |
| Errors | Branded 401 / 403 / 404 / 419 / 422 / 429 / 500 / 503 pages with i18n + auto-refresh on 503. | #3 |
| Customer profile | Phone input now ships an international flag/dial-code dropdown with E.164 normalisation on submit. Avatar upload (with 256×256 GD resize). GDPR data export (Article 20). Inactive-account purge (Article 5) with reminders at D-30/D-7/D-1. | #4, #5, #12 |
| Helpdesk | Editor: Ctrl+V to paste screenshots, drag-drop file zone, server-rendered Markdown preview, double-submit guard, scroll on long messages. Configurable ticket access rules (require active service, Premium option, block priorities…). SLA tracking with `helpdesk:notify-sla-breach` + canned macros. | #7, #8, #9 |
| Security | Permission scopes (per-department / per-product) + role hierarchy. SMS MFA driver (Twilio out of the box, pluggable). Email MFA + encrypted recovery codes (came upstream). | #10, #11 |
| A11y | Mobile pass: keyboard skip-link, `:focus-visible` outlines, WCAG 2.5.5 touch targets, `prefers-reduced-motion`. Lighthouse CI baseline. | #6 |
| Services | Live status endpoint polled by the customer page (15 s, Page Visibility-aware, exponential backoff). | #15 |
| Promo codes | Promo input is now available directly on `/basket/add` (the config page) — no more "add product first, then go to /store/basket". | #16-basket |
| Robustness | Extension sandbox: a broken provider can no longer 500 the whole app. `APP_SAFE_BOOT=true` env var skips every extension. | #17 |

## Upgrading

### One-shot command

```bash
git fetch && git checkout v2.16
php artisan clientxcms:upgrade
```

`clientxcms:upgrade` is the official entry point. It:

1. Runs pre-flight checks (PHP version, writable storage, .env
   present, vendor/ installed).
2. Optionally takes a MySQL dump when `BACKUP_BEFORE_UPGRADE=true`
   is set in the env (default off — operators using managed DB
   backups don't pay the dump time).
3. Runs `composer install --no-dev --optimize-autoloader` (skipped
   when `--no-composer` is passed for systems where composer is
   driven by an outer wrapper).
4. Builds the JS/CSS bundle (`npm ci && npm run build`), idem with
   `--no-assets`.
5. Runs `php artisan migrate --force`.
6. Refreshes the gitignored `lang/` translations from
   ctx-translations: `php artisan translations:import` for every
   locale declared in `app_enabled_locales`.
7. Clears every cache (`php artisan optimize:clear`).
8. Touches `storage/installed` so the install wizard doesn't kick
   back in.
9. Runs `php artisan clientxcms:check` as a post-flight smoke test.

### Manual upgrade (if you skip the command)

```bash
composer install --no-dev --optimize-autoloader --no-progress
npm ci --no-fund --no-audit
npm run build
php artisan migrate --force
for L in fr_FR en_GB es_ES; do php artisan translations:import --locale="$L"; done
php artisan optimize:clear
touch storage/installed
```

The `lang/` directory is gitignored and is populated by
`translations:import`. New v2.16 string keys also ship inside the
repo under `resources/translations/v216/` and are loaded under the
`v216::` namespace — no extra step needed.

### Migrations added in v2.16

| Migration | What it adds | Reversible |
|---|---|---|
| `2026_05_23_120000_add_status_to_service_renewals_table` | service_renewals.status + virtual pending_lock_key + partial unique | yes |
| `2026_05_23_140000_add_avatar_to_users_and_admins` | avatar_path on customers + admins | yes |
| `2026_05_23_150000_create_support_access_rules_table` | helpdesk access rules | yes |
| `2026_05_23_160000_add_sla_and_macros_to_helpdesk` | SLA columns + support_macros | yes |
| `2026_05_23_170000_add_scope_and_parent_to_permissions` | scope_type/scope_id pivot + parent_role_id | yes |
| `2026_05_23_180000_create_invoice_sequences_and_pdf_hash` | atomic invoice number counter + pdf_sha256 + credit_notes | yes |
| `2026_05_23_190000_create_usage_billing_tables` | service_usage_metrics + product_metered_rates | yes |

Every migration adds **nullable** columns or **new** tables. None
renames or drops anything that v2.15 relied on. `php artisan
migrate:rollback` works for each.

## Rolling back

If you need to abandon v2.16:

```bash
# 1. Roll back code
git checkout v2.15

# 2. Reverse the v2.16 migrations one by one
php artisan migrate:rollback --path=database/migrations/2026_05_23_190000_create_usage_billing_tables.php
php artisan migrate:rollback --path=database/migrations/2026_05_23_180000_create_invoice_sequences_and_pdf_hash.php
php artisan migrate:rollback --path=database/migrations/2026_05_23_170000_add_scope_and_parent_to_permissions.php
php artisan migrate:rollback --path=database/migrations/2026_05_23_160000_add_sla_and_macros_to_helpdesk.php
php artisan migrate:rollback --path=database/migrations/2026_05_23_150000_create_support_access_rules_table.php
php artisan migrate:rollback --path=database/migrations/2026_05_23_140000_add_avatar_to_users_and_admins.php
php artisan migrate:rollback --path=database/migrations/2026_05_23_120000_add_status_to_service_renewals_table.php

# 3. Composer + assets
composer install --no-dev
npm ci && npm run build

# 4. Cache
php artisan optimize:clear
```

Customer-facing data is preserved across the rollback — soft-delete
on customers/services/invoices is untouched, every v2.16 column is
nullable or has a sensible default.

## Recovering from a broken extension

v2.16 introduces a sandbox around extension boot. If a third-party
extension throws, the framework auto-disables it and surfaces the
error in `/admin/extensions`. If the admin area itself won't render
because the broken extension is registered too early, force
"safe boot":

```bash
APP_SAFE_BOOT=true php artisan tinker
# ... or, for the web request path:
echo "APP_SAFE_BOOT=true" >> .env
php artisan config:clear
```

Then visit `/admin/extensions` and disable the offending extension.

## Telemetry (opt-in)

`php artisan telemetry` ships an anonymous "I'm running v2.16" ping
to the ClientXCMS license server. Disable with
`TELEMETRY_ENABLED=false` in your env.

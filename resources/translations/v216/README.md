# v2.16 — packaged translations

The `lang/` directory at the repository root is `.gitignore`d: it is
populated at runtime by `php artisan translations:import-files`, which
downloads JSON files from
[`ClientXCMS/ctx-translations`](https://github.com/ClientXCMS/ctx-translations)
and turns them into the standard Laravel PHP arrays.

The folders below ship inline translations for **new strings introduced
in v2.16 only**. They are registered as a separate translation namespace
named `v216` by `App\Providers\V216TranslationServiceProvider`, so any
key can be referenced from Blade as:

```php
__('v216::errors.404.title')
```

Locale fallback chain:

1. The current locale (`fr_FR`, `en_GB`, `es_ES`, …)
2. `en_GB` (the canonical source)
3. The key itself (raw string), à la Laravel

## How to add a new key

1. Add it to `en_GB/<file>.php`.
2. Translate it in `fr_FR/<file>.php`, `es_ES/<file>.php`, and any other
   supported locale.
3. Open a follow-up PR on the public `ctx-translations` repo so the
   community translations stay the source of truth. Once those land, the
   key can be migrated out of this folder.

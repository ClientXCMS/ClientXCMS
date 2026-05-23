# v2.16 — packaged translations

See `resources/translations/v216/README.md` in any v2.16 PR for the
rationale: the root `lang/` directory is `.gitignore`d and populated
at runtime from the public ctx-translations repo, so new translation
keys ship inside the application instead, under the `v216::` namespace
registered by `App\Providers\V216TranslationServiceProvider`.

Usage from Blade: `__('v216::a11y.skip_to_content')`.

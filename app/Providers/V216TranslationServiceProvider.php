<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * v2.16 — Registers the `v216::` translation namespace so the new
 * translation keys introduced in this release can ship inside this
 * repository without colliding with the gitignored `lang/` directory
 * (which is populated at runtime by `translations:import-files` from
 * the public ctx-translations repo).
 *
 * Once the same keys have been merged into ctx-translations, the
 * corresponding files under resources/translations/v216 can be removed
 * and call sites updated to drop the `v216::` prefix. Until then this
 * provider guarantees the new error pages have working translations on
 * any fresh install.
 */
class V216TranslationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(
            resource_path('translations/v216'),
            'v216'
        );
    }
}

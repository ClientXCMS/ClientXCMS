<?php

namespace Tests;

use App\Console\Kernel;

trait RefreshExtensionDatabase
{
    public function migrateExtension(?string $addon = null): void
    {
        if ($addon) {
            $this->artisan('clientxcms:db-extension', ['--extension' => $addon]);
            $this->app[Kernel::class]->setArtisan(null);
        } else {
            $this->artisan('clientxcms:db-extension', ['--all' => true]);
            $this->app[Kernel::class]->setArtisan(null);
        }
    }
}

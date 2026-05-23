<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Console\Commands\Install;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * v2.16 — Health check that runs as the last step of
 * clientxcms:upgrade and can also be invoked on its own to validate
 * an install. Returns non-zero on failure so a CI wrapper can fail
 * the deploy.
 */
class ClientxcmsCheckCommand extends Command
{
    protected $signature = 'clientxcms:check {--json : machine-readable output}';

    protected $description = 'Verify the current install is in a healthy state';

    public function handle(): int
    {
        $checks = [];

        // 1. DB reachable + key v2.16 tables present.
        try {
            DB::connection()->getPdo();
            $checks['db_connection'] = true;
        } catch (\Throwable $e) {
            $checks['db_connection'] = false;
            $checks['db_error'] = mb_substr($e->getMessage(), 0, 200);
        }

        $expectedTables = [
            'customers', 'invoices', 'service_renewals', 'support_tickets',
            // v2.16 additions (skipped silently when missing so this
            // command also reports v2.15 cleanly)
            'invoice_sequences', 'support_access_rules', 'support_macros',
            'service_usage_metrics', 'product_metered_rates', 'credit_notes',
        ];
        $missing = [];
        foreach ($expectedTables as $table) {
            if (! Schema::hasTable($table)) {
                $missing[] = $table;
            }
        }
        $checks['v216_tables_missing'] = $missing;

        // 2. APP_KEY set
        $checks['app_key'] = ! empty(config('app.key'));

        // 3. storage/ writable
        $checks['storage_writable'] = File::isWritable(storage_path());

        // 4. storage/installed present
        $checks['installed_flag'] = File::exists(storage_path('installed'));

        // 5. public/build present (vite output)
        $checks['vite_bundle'] = File::exists(public_path('build/manifest.json'));

        // 6. extension sandbox boot errors (any extension currently
        //    flagged as failing?)
        $checks['extensions_boot_errors'] = [];
        try {
            $cache = \App\Extensions\ExtensionManager::readExtensionJson();
            foreach (['modules', 'addons', 'themes'] as $bucket) {
                foreach ($cache[$bucket] ?? [] as $row) {
                    if (isset($row['boot_error'])) {
                        $checks['extensions_boot_errors'][] = [
                            'uuid' => $row['uuid'] ?? '?',
                            'message' => $row['boot_error']['message'] ?? '',
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            $checks['extensions_boot_errors'] = ['readExtensionJson failed: ' . $e->getMessage()];
        }

        if ($this->option('json')) {
            $this->line(json_encode($checks, JSON_PRETTY_PRINT));
        } else {
            foreach ($checks as $name => $value) {
                if (is_bool($value)) {
                    $this->line(sprintf(' %s %s', $value ? '✓' : '✗', $name));
                } else {
                    $this->line(sprintf(' • %s: %s', $name, is_array($value) ? implode(', ', array_map(
                        fn ($v) => is_array($v) ? json_encode($v) : (string) $v,
                        $value
                    )) : (string) $value));
                }
            }
        }

        // Failure conditions: DB unreachable, missing app key, storage
        // not writable, vite missing.
        $fatal = $checks['db_connection'] === false
            || $checks['app_key'] !== true
            || $checks['storage_writable'] !== true
            || $checks['vite_bundle'] !== true;

        return $fatal ? self::FAILURE : self::SUCCESS;
    }
}

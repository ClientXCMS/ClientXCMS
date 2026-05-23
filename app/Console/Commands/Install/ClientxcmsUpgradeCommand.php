<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Console\Commands\Install;

use App\Services\Core\LocaleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * v2.16 — One-shot upgrade entry point.
 *
 * Operators run `php artisan clientxcms:upgrade` after `git pull`.
 * The command sequences: pre-flight checks, optional DB backup,
 * composer install, asset build, migrations, translations import,
 * cache clear, post-flight smoke test.
 *
 * Every step is idempotent. The command never deletes user data and
 * exits non-zero on the first irrecoverable failure so a CI/CD
 * wrapper can react.
 */
class ClientxcmsUpgradeCommand extends Command
{
    protected $signature = 'clientxcms:upgrade
                            {--no-composer : skip composer install}
                            {--no-assets : skip npm/build}
                            {--no-translations : skip translations:import}
                            {--no-backup : skip the optional DB dump}';

    protected $description = 'Run every step required to upgrade a ClientXCMS install to the current code version';

    public function handle(): int
    {
        $this->info(sprintf('--- ClientXCMS upgrade — %s ---', config('app.name')));

        if (! $this->preflight()) {
            return self::FAILURE;
        }
        $this->info('✓ pre-flight checks');

        if (! $this->option('no-backup')) {
            $this->maybeBackupDatabase();
        }

        if (! $this->option('no-composer')) {
            $this->info('Composer install (no-dev)…');
            if (! $this->runProcess(['composer', 'install', '--no-dev', '--optimize-autoloader', '--no-interaction', '--no-progress'])) {
                $this->error('composer install failed — aborting');
                return self::FAILURE;
            }
        }

        if (! $this->option('no-assets')) {
            $this->info('npm ci + build…');
            if (! $this->runProcess(['npm', 'ci', '--no-fund', '--no-audit', '--silent'])
                || ! $this->runProcess(['npm', 'run', 'build', '--silent'])) {
                $this->error('asset build failed — aborting');
                return self::FAILURE;
            }
        }

        $this->info('Migrating database…');
        $exit = Artisan::call('migrate', ['--force' => true]);
        $this->line(Artisan::output());
        if ($exit !== 0) {
            $this->error('migrations failed — aborting');
            return self::FAILURE;
        }

        if (! $this->option('no-translations')) {
            $this->info('Importing translations…');
            foreach ($this->desiredLocales() as $locale) {
                $this->line("  • {$locale}");
                Artisan::call('translations:import', ['--locale' => $locale]);
            }
        }

        $this->info('Clearing caches…');
        Artisan::call('optimize:clear');
        $this->line(Artisan::output());

        // Mark install — keeps the install wizard from kicking back
        // in after a fresh upgrade.
        File::ensureDirectoryExists(storage_path());
        if (! File::exists(storage_path('installed'))) {
            File::put(storage_path('installed'), 'upgraded=' . now()->toIso8601String());
        }

        $this->info('Post-flight checks…');
        Artisan::call('clientxcms:check');
        $this->line(Artisan::output());

        $this->info('✓ Upgrade complete.');
        return self::SUCCESS;
    }

    private function preflight(): bool
    {
        $ok = true;

        if (version_compare(PHP_VERSION, '8.3.0', '<')) {
            $this->error('PHP 8.3+ required, found ' . PHP_VERSION);
            $ok = false;
        }
        if (! File::exists(base_path('.env'))) {
            $this->error('.env file is missing');
            $ok = false;
        }
        if (! File::isWritable(storage_path())) {
            $this->error('storage/ is not writable');
            $ok = false;
        }
        if (! File::exists(base_path('vendor/autoload.php'))) {
            $this->warn('vendor/ not found — composer install will be required');
        }
        foreach (['mbstring', 'intl', 'gd', 'pdo_mysql', 'zip', 'xml'] as $ext) {
            if (! extension_loaded($ext)) {
                $this->error("PHP extension `{$ext}` is missing");
                $ok = false;
            }
        }

        return $ok;
    }

    private function maybeBackupDatabase(): void
    {
        if (! filter_var(env('BACKUP_BEFORE_UPGRADE', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }
        $db = config('database.connections.' . config('database.default'));
        $target = storage_path('backups/' . now()->format('Y-m-d_H-i-s') . '.sql');
        File::ensureDirectoryExists(dirname($target));

        $cmd = [
            'mysqldump',
            '--host=' . ($db['host'] ?? '127.0.0.1'),
            '--port=' . ($db['port'] ?? '3306'),
            '--user=' . ($db['username'] ?? ''),
            '--password=' . ($db['password'] ?? ''),
            '--single-transaction',
            '--quick',
            '--routines',
            $db['database'] ?? '',
        ];
        $this->info("Backing up DB to {$target}…");
        $process = new Process($cmd);
        $process->setTimeout(600);
        $process->run();
        if (! $process->isSuccessful()) {
            $this->warn('mysqldump failed; continuing without backup. ' . $process->getErrorOutput());
            return;
        }
        File::put($target, $process->getOutput());
        $this->info("✓ DB backup: {$target}");
    }

    private function desiredLocales(): array
    {
        try {
            $configured = json_decode((string) setting('app_enabled_locales', '["en_GB"]'), true);
            if (is_array($configured) && ! empty($configured)) {
                return array_unique(array_filter($configured));
            }
        } catch (\Throwable $e) {
            // settings table may not exist yet on a brand-new install
        }
        return ['en_GB', 'fr_FR'];
    }

    private function runProcess(array $command): bool
    {
        $process = new Process($command, base_path());
        $process->setTimeout(900);
        $process->run(function ($type, $buffer) {
            $this->getOutput()->write($buffer);
        });
        return $process->isSuccessful();
    }
}

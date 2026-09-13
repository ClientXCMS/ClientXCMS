<?php

/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2025
 */

namespace App\Console\Commands\Extension;

use App\Extensions\ExtensionType;
use Illuminate\Console\Command;
use Throwable;

class DatabaseExtensionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clientxcms:db-extension {--action=migrate} {--extension=} {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate the migrations folder of a extension.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $types = [ExtensionType::Module, ExtensionType::Addon, ExtensionType::Theme];
        $extensions = [];
        if ($this->option('extension') == null && $this->option('all') == null) {
            $this->error('No extension specified.');

            return;
        }
        if ($this->option('extension') !== null && ! preg_match('/^[a-zA-Z0-9_-]+$/', $this->option('extension'))) {
            $this->error('Invalid extension identifier.');

            return;
        }
        $requested = $this->option('extension');
        $extension = null;
        foreach ($types as $type) {
            $directories = \File::directories(base_path($type->directory()));
            foreach ($directories as $directory) {
                $uuid = basename($directory);
                // directory(), not basename(): themes live under resources/themes
                $migrations = $type->directory().'/'.$uuid.'/database/migrations';
                if (app('extension')->extensionIsEnabled($uuid)) {
                    $extensions[] = $migrations;
                }
                if ($requested === $uuid) {
                    $extension = $migrations;
                }
            }
        }
        if ($this->hasOption('all') && $this->option('all')) {
            foreach ($extensions as $extension) {
                try {
                    \Artisan::call('migrate', [
                        '--force' => true,
                        '--path' => $extension,
                    ]);
                    $this->comment(\Artisan::output());
                } catch (Throwable $e) {
                    $this->error("Migration failed for {$extension}: ".$e->getMessage());
                }
            }

            return;
        }
        // A named extension must not depend on which other ones happen to be enabled
        if ($requested !== null && $extension === null) {
            $this->error("Extension not found: {$requested}");

            return;
        }
        if ($extension === null) {
            if (empty($extensions)) {
                $this->error('No extensions found in the modules or addons folder.');

                return;
            }
            $extension = $this->choice('Which extension do you want to create a migration for?', $extensions);
        }
        $extension = sanitize($extension);
        $action = sanitize($this->option('action'));
        if ($action == 'migrate') {
            $this->migrate($extension);
        } elseif ($action == 'rollback') {
            $this->rollback($extension);
        } elseif ($action == 'seed') {
            $this->seed($extension);
        } else {
            $this->error('Invalid action. Available actions are migrate, rollback and seed.');
        }
    }

    private function migrate($extension)
    {
        $this->info('Migrating extension: '.$extension);
        \Artisan::call('migrate', ['--path' => $extension, '--force' => true]);
        $this->info(\Artisan::output());
        $this->info('Extension migrated successfully.');
    }

    private function rollback($extension)
    {
        $this->info('Rolling back extension: '.$extension);
        \Artisan::call('migrate:rollback', ['--path' => $extension, '--force' => true]);
        $this->info(\Artisan::output());
        $this->info('Extension rolled back successfully.');
    }

    private function seed($extension)
    {
        $this->info('Seeding extension: '.$extension);
        \Artisan::call('db:seed', ['--path' => $extension, '--force' => true]);
        $this->info(\Artisan::output());
        $this->info('Extension seeded successfully.');
    }
}

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

namespace App\Console\Commands\Install;

use App\Helpers\EnvEditor;
use Illuminate\Console\Command;

class InstallDBCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clientxcms:install-db {--username=} {--password=} {--database=} {--host=} {--port=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the database for the clientxcms application (warning : this will drop all tables)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! app('installer')->isEnvExists() || ! app('installer')->isEnvWritable()) {
            $this->error('Env file is not configured.');

            return;
        }
        EnvEditor::updateEnv([
            'DB_USERNAME' => $this->option('username') ?? $this->ask('Database username'),
            'DB_PASSWORD' => $this->option('password') ?? $this->secret('Database password'),
            'DB_DATABASE' => $this->option('database') ?? $this->ask('Database name'),
            'DB_HOST' => $this->option('host') ?? $this->ask('Database host'),
            'DB_PORT' => $this->option('port') ?? $this->ask('Database port'),
        ]);
        if (app('installer')->tryConnectDatabase()) {
            $this->info('Database connection established successfully.');
            \Artisan::call('migrate:fresh', ['--force' => true]);
        } else {
            $this->error('Database connection failed.');
        }
    }
}

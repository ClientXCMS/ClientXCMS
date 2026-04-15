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

class InstallOauthClientCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clientxcms:install-oauth-client {--client_id=} {--client_secret=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the oauth client for the clientxcms application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (app('installer')->isEnvExists() && app('installer')->isEnvWritable()) {

            EnvEditor::updateEnv([
                'OAUTH_CLIENT_ID' => $this->option('client_id') ?? $this->ask('Oauth Client ID'),
                'OAUTH_CLIENT_SECRET' => $this->option('client_secret') ?? $this->secret('Oauth Client Secret'),
            ]);
            $this->info('Oauth Client installed successfully.');
        } else {
            $this->error('Env file is not configured.');
        }
    }
}

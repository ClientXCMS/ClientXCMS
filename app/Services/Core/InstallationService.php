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


namespace App\Services\Core;

use App\DTO\Install\Prerequisites;
use App\Helpers\EnvEditor;
use App\Providers\AppServiceProvider;
use Illuminate\Http\Response;

class InstallationService
{
    public function isEnvWritable(): bool
    {
        return is_writable(base_path('.env'));
    }

    public function isEnvExists(): bool
    {
        return file_exists(base_path('.env'));
    }

    public function isFolderWritable(): bool
    {
        return is_writable(base_path());
    }

    public function canInstalled(): bool
    {
        return $this->isEnvExists() && $this->isEnvWritable();
    }

    public function isEnvFileIsValid(): Response
    {
        if (! $this->isEnvExists()) {
            return new Response('Please create .env file and configure database connection.', 500);
        }
        if (! $this->isEnvWritable()) {
            return new Response('Please make .env file writable.', 500);
        }
        if (! $this->tryConnectDatabase()) {
            return new Response('Please check database connection.', 500);
        }
        if (! $this->isFolderWritable()) {
            return new Response('Please make root folder writable.', 500);
        }
        if (! $this->hasAppKey()) {
            return new Response('Please generate app key with "php artisan key:generate" command on your CLIENTXCMS folder', 500);
        }
        $prerequisites = new Prerequisites;
        if (! empty($prerequisites->errors)) {
            return new Response('Prerequisites : '.implode('<br>', $prerequisites->errors), 500);
        }

        return new Response;
    }

    public function tryConnectDatabase(): bool
    {
        try {
            app()->make('db')->getPdo();

            return true;
        } catch (\Exception $e) {
            if (env('APP_DEBUG')) {
                throw $e;
            }

            return false;
        }
    }

    public function isMigrated(): bool
    {
        return app()->make('db')->getSchemaBuilder()->hasTable('services');
    }

    public function hasOauthLicence()
    {
        if (setting('app_license_refresh_token') == null) {
            return false;
        }
        if ($this->isEnvExists() && $this->isEnvWritable()) {
            return env('OAUTH_CLIENT_ID') && env('OAUTH_CLIENT_SECRET');
        }

        return false;
    }

    public function hasAppKey(): bool
    {
        return env('APP_KEY');
    }

    public function updateEnv(array $params)
    {
        EnvEditor::updateEnv($params);
    }

    public function createInstalledFile(): void
    {
        file_put_contents(storage_path('installed'), 'version='.AppServiceProvider::VERSION.';time='.time());
    }
}
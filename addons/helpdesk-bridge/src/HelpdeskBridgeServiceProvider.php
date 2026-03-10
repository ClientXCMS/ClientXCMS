<?php

namespace App\Addons\HelpdeskBridge;

use App\Addons\HelpdeskBridge\Http\Controllers\Admin\SettingsController;
use App\Models\Admin\Permission;
use App\Extensions\BaseAddonServiceProvider;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Route;

class HelpdeskBridgeServiceProvider extends BaseAddonServiceProvider
{
    protected string $name = 'Helpdesk Bridge';

    protected string $version = '1.0';

    protected string $uuid = 'helpdesk-bridge';

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadTranslations();
        $this->loadViews();
        $this->loadMigrations();
        $this->loadRoutes();

        if (! is_installed()) {
            return;
        }

        try {
            $this->registerSettingsCard();
        } catch (QueryException) {
            // Settings tables may not be ready yet.
        }
    }

    public function loadRoutes()
    {
        Route::middleware(['web', 'admin'])
            ->prefix(admin_prefix('settings/helpdesk-bridge'))
            ->name('admin.helpdesk_bridge.')
            ->group(__DIR__.'/routes/admin.php');

        Route::middleware('api')
            ->prefix('api/client')
            ->name('api.client.')
            ->group(__DIR__.'/routes/api-client.php');
    }

    private function registerSettingsCard(): void
    {
        app('settings')->addCardItem(
            'helpdesk',
            'helpdesk_bridge',
            'helpdesk-bridge::helpdesk_bridge.admin.settings.title',
            'helpdesk-bridge::helpdesk_bridge.admin.settings.description',
            'bi bi-envelope-at',
            [SettingsController::class, 'index'],
            Permission::MANAGE_SETTINGS
        );
    }
}

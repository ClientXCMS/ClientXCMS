<?php

namespace App\Addons\HelpdeskBridge;

use App\Addons\HelpdeskBridge\Http\Controllers\Admin\SettingsController;
use App\Extensions\BaseAddonServiceProvider;
use App\Models\Admin\Permission;
use Illuminate\Support\Facades\Route;

class HelpdeskBridgeServiceProvider extends BaseAddonServiceProvider
{
    protected string $name = 'Helpdesk Bridge';

    protected string $version = '1.0.0';

    protected string $uuid = 'helpdesk-bridge';

    public function boot(): void
    {
        $this->loadViews();
        $this->registerSettingsItem();
        $this->loadRoutes();
    }

    private function registerSettingsItem(): void
    {
        app('settings')->addCardItem(
            'helpdesk',
            'helpdesk_bridge',
            'Helpdesk Bridge',
            'Configuration du bridge email entrant Helpdesk',
            'bi bi-envelope-arrow-down',
            [SettingsController::class, 'show'],
            Permission::MANAGE_SETTINGS
        );
    }

    public function loadRoutes()
    {
        Route::middleware('api')->prefix('api/client')->group($this->addonPath('src/routes/api-client.php'));

        Route::name('admin.helpdesk-bridge.')
            ->prefix(admin_prefix('helpdesk-bridge'))
            ->middleware('admin')
            ->group($this->addonPath('src/routes/admin.php'));
    }
}

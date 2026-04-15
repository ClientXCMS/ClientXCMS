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

namespace App\Providers;

use App\Core\Admin\Dashboard\AdminCardWidget;
use App\Core\Menu\FrontMenuItem;
use App\Models\Account\Customer;
use Illuminate\Support\ServiceProvider;

class ClientareaServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app['extension']->addFrontMenuItem(new FrontMenuItem('clientarea', 'front.client.index', 'bi bi-speedometer', 'global.clientarea', 1));
        $this->app['extension']->addFrontMenuItem(new FrontMenuItem('services', 'front.services.index', 'bi bi-box2', 'client.services.index', 2));
        $this->app['extension']->addFrontMenuItem(new FrontMenuItem('emails', 'front.emails.index', 'bi bi-envelope', 'client.emails.index', 4));
        $this->app['extension']->addFrontMenuItem(new FrontMenuItem('profile', 'front.profile.index', 'bi bi-person-lines-fill', 'client.profile.index', 5));
        $this->app['extension']->addAdminCardsWidget(new AdminCardWidget('customers_confirmation_canvas', function () {
            $data = Customer::selectRaw('count(*) as count, is_confirmed')->groupBy('is_confirmed')->get();
            $dto = new \App\DTO\Admin\Dashboard\CustomerConfirmationCanvasDTO($data);

            return view('admin.dashboard.cards.customers-confirmation-canvas', ['dto' => $dto]);
        }, 'admin.show_customers', 1, 'services_canvas'));
    }
}

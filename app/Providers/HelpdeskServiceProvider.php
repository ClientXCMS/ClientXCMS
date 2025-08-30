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
use App\Core\Admin\Dashboard\AdminCountWidget;
use App\Core\Menu\AdminMenuItem;
use App\Core\Menu\FrontMenuItem;
use App\Http\Controllers\Admin\Helpdesk\HelpdeskSettingsController;
use App\Http\Controllers\Admin\Helpdesk\Support\DepartmentController;
use App\Models\Admin\Permission;
use App\Models\Helpdesk\SupportTicket;
use Illuminate\Database\QueryException;
use Illuminate\Support\ServiceProvider;

class HelpdeskServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! is_installed()) {
            return;
        }
        try {
            $this->registerWidgets();
        } catch (QueryException $e) {
            // Do nothing
        }
    }

    private function registerWidgets()
    {
        $ticketWidgets = new AdminCountWidget('tickets', 'bi bi-chat-left-text', 'helpdesk.admin.open_tickets', function () {
            return SupportTicket::where('status', 'open')->count();
        }, 'admin.manage_tickets');
        $this->app['extension']->addAdminCountWidget($ticketWidgets);
        $this->app['extension']->addAdminCardsWidget(new AdminCardWidget('support', function () {
            $tickets = SupportTicket::where('status', 'open')->limit(3)->get();
            $tickets = $tickets->filter(function (SupportTicket $ticket) {
                return $ticket->staffCanView(auth('admin')->user());
            });

            return view('admin.helpdesk.cards.tickets', ['tickets' => $tickets]);
        }, 'admin.manage_tickets', 2));
        $this->app['settings']->addCard('helpdesk', 'helpdesk.admin.title', 'helpdesk.admin.description', 5, null, true);
        $this->app['settings']->addCardItem('helpdesk', 'helpdesk_settings', 'helpdesk.admin.settings.title', 'helpdesk.admin.settings.description', 'bi bi-gear', [HelpdeskSettingsController::class, 'showSettings'], Permission::MANAGE_SETTINGS);
        $this->app['settings']->addCardItem('helpdesk', 'helpdesk_departments', 'helpdesk.admin.departments.title', 'helpdesk.admin.departments.description', 'bi bi-building', action([DepartmentController::class, 'index']), 'admin.manage_departments');
        $this->app['extension']->addAdminMenuItem(new AdminMenuItem('helpdesk', 'admin.helpdesk.tickets.index', 'bi bi-chat-left-text', 'helpdesk.admin.title', 6, 'admin.reply_tickets'));
        $this->app['extension']->addFrontMenuItem(new FrontMenuItem('helpdesk', 'front.support.index', 'bi bi-chat-left-text', 'helpdesk.admin.title', 4));
    }
}

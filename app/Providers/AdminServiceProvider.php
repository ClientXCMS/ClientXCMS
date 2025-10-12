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
use App\Models\Account\Customer;
use App\Models\Admin\Permission;
use Cache;
use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;
use View;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void {}

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerMenuItems();
        if (is_installed()) {
            $this->registerAdminCountWidgets();
        }
        View::share('appVersion', $this->versionData()['version'] ?? 'undefined');
        View::share('appIsGit', $this->versionData()['is_git'] ?? false);
    }

    private function registerMenuItems()
    {
        $this->app['extension']->addAdminMenuItem((new AdminMenuItem('dashboard', 'admin.dashboard', 'bi bi-speedometer2', 'admin.dashboard.title', 1, Permission::ALLOWED)));
        $this->app['extension']->addAdminMenuItem((new AdminMenuItem('earn', 'admin.earn', 'bi bi-cash-coin', 'admin.dashboard.earn.title', 2, 'admin.earn_page')));
        $this->app['extension']->addAdminMenuItem((new AdminMenuItem('customers', 'admin.customers.index', 'bi bi-people', 'admin.customers.title', 3, 'admin.show_customers')));
        $this->app['extension']->addAdminMenuItem((new AdminMenuItem('emails', 'admin.emails.index', 'bi bi-envelope', 'admin.emails.title', 100, 'admin.show_emails')));
    }

    private function registerAdminCountWidgets()
    {

        $cron = new AdminCountWidget('cron', 'bi bi-clock-history', 'admin.dashboard.widgets.cron', function () {
            $date = setting('app_cron_last_run', null);
            if ($date == null) {
                return __('admin.dashboard.tooltips.cron.never');
            } else {
                return Carbon::parse($date)->diffForHumans();
            }
        }, 'admin.show_logs', true);
        $this->app['extension']->addAdminCountWidget($cron);
        $usersWidgets = new AdminCountWidget('users', 'bi bi-people', 'global.users', function () {
            return Customer::where('is_deleted', false)->count();
        }, 'admin.manage_customers');
        $this->app['extension']->addAdminCountWidget($usersWidgets);
        $this->app['extension']->addAdminCardsWidget(new AdminCardWidget('last_login', function () {
            $accounts = Customer::where('last_login', '!=', null)->orderBy('last_login', 'desc')->limit(3)->get();

            return view('admin.dashboard.cards.last-login', ['accounts' => $accounts]);
        }, 'admin.dashboard_last_login', 2));

        $this->app['extension']->addAdminCardsWidget(new AdminCardWidget('customer_search', function () {
            $fields = [
                'id' => 'User ID',
                'email' => __('global.email'),
                'firstname' => __('global.firstname'),
                'lastname' => __('global.lastname'),
                'phone' => __('global.phone'),
                'service_id' => 'Service ID',
                'invoice_id' => 'Invoice ID',
            ];

            return view('admin.dashboard.cards.customer-search', ['fields' => $fields]);
        }, 'admin.manage_customers', 1, 'services_canvas'));
        $this->app['settings']->addCardItem('security', 'roles', 'admin.roles.title', 'admin.roles.description', 'bi bi-person-badge', route('admin.roles.index'), 'admin.manage_roles');
    }

    /**
     * Return version information for the footer.
     *
     * @return array
     * @see https://github.com/pterodactyl/panel/blob/0.7-develop/app/Providers/AppServiceProvider.php
     */
    protected function versionData()
    {
        return Cache::remember('git-version', 5, function () {
            if (file_exists(base_path('.git/HEAD'))) {
                $head = explode(' ', file_get_contents(base_path('.git/HEAD')));

                if (array_key_exists(1, $head)) {
                    $path = base_path('.git/' . trim($head[1]));
                }
            }

            if (isset($path) && file_exists($path)) {
                return [
                    'version' => substr(file_get_contents($path), 0, 8),
                    'is_git' => true,
                ];
            }

            return [
                'version' => AppServiceProvider::VERSION,
                'is_git' => false,
            ];
        });
    }
}

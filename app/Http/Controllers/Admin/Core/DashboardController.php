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
 * Year: 2025
 */
namespace App\Http\Controllers\Admin\Core;

use App\Core\Admin\Dashboard\AdminCardWidget;
use App\Core\Admin\Dashboard\AdminCountWidget;
use App\DTO\Admin\Dashboard\BestSellingProductsDTO;
use App\DTO\Admin\Dashboard\IntelligentSearchDTO;
use App\Models\Admin\Permission;
use App\Models\Billing\Invoice;
use App\Models\Helpdesk\SupportTicket;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController
{
    public function index()
    {
        $cards = app('extension')->getAdminCardsWidgets();
        $widgets = app('extension')->getAdminCountWidgets();

        $cards = collect($cards)->filter(function (AdminCardWidget $card) {
            return auth('admin')->user()->can($card->permission);
        });
        $widgets = collect($widgets)->filter(function (AdminCountWidget $widget) {
            return auth('admin')->user()->can($widget->permission);
        });
        $data = [
            'widgets' => $widgets,
            'cards' => $cards,
            'good_message' => $this->goodMessage(),
            'healthcheck' => $this->checkHealthCheck(),
        ];

        return view('admin.dashboard.dashboard', $data);
    }

    public function intelligentSearch(Request $request)
    {
        $query = $request->query('q');

        return response()->json((new IntelligentSearchDTO($query))->toArray());
    }

    public function earn(Request $request)
    {
        $data = $this->exportEarn($request);

        return view('admin.dashboard.earn', $data);
    }

    public function exportEarn(Request $request): array
    {
        $validator = $request->validate([
            'start' => 'nullable|date|before_or_equal:end',
            'end' => 'nullable|date',
            'year' => 'nullable|integer|min:2000|max:'.now()->year,
            'services_from' => 'nullable|date',
            'limit' => 'nullable|integer|min:-1',
        ]);
        staff_aborts_permission('admin.earn_page');
        $isCustom = request()->has('start') && request()->has('end');
        $year = $request->get('year', 'none');
        $start = $isCustom ? Carbon::createFromFormat('Y-m-d', request()->get('start')) : $this->firstBillingDate();
        $end = $isCustom ? Carbon::createFromFormat('Y-m-d', request()->get('end')) : now();
        $limit = $request->get('limit', 10);
        $servicesFrom = $request->has('services_from') ? Carbon::createFromFormat('Y-m-d', $request->get('services_from')) : now()->startOfMonth();

        return [
            'widgets' => BestSellingProductsDTO::makeWidgets($isCustom, $start, $end),
            'bestSelling' => BestSellingProductsDTO::bestSellingProducts(),
            'gatewaysSources' => BestSellingProductsDTO::getGatewaysSources(),
            'servicesStatistics' => BestSellingProductsDTO::getServicesStatistics($servicesFrom, $limit),
            'servicesFrom' => $servicesFrom,
            'earnMonths' => BestSellingProductsDTO::compareYear($year != 'none' ? $year : null),
            'isCustom' => $isCustom,
            'start' => $start,
            'end' => $end,
            'year' => $year,
            'years' => collect(range(2000, now()->year - 1))->mapWithKeys(fn ($year) => [$year => $year])->put('none', __('global.none')),
            'servicesSubscriptions' => BestSellingProductsDTO::getServicesSubscriptions(),
            'lastorders' => BestSellingProductsDTO::getLastOrders(),
            'lastrenewals' => BestSellingProductsDTO::getLastRenewals(),
            'relatedBilled' => BestSellingProductsDTO::getRelatedBilled(),
            'bestCustomers' => BestSellingProductsDTO::getBestCustomers($limit),
            'limits' => [10 => '10', 20 => '20', 50 => '50', 100 => '100', -1 => __('global.any')],
            'limit' => $limit,
        ];
    }

    private function firstBillingDate()
    {
        $invoice = Invoice::first();

        return $invoice ? $invoice->created_at : now();
    }

    private function goodMessage()
    {
        $hour = now()->hour;
        if ($hour >= 5 && $hour < 12) {
            return 'admin.dashboard.good_morning';
        } elseif ($hour >= 12 && $hour < 18) {
            return 'admin.dashboard.good_afternoon';
        } else {
            return 'admin.dashboard.good_evening';
        }
    }

    private function checkHealthCheck()
    {
        $heathcheck = [];
        $notification_error = \Cache::get('notification_error');
        $tickets_assigned = SupportTicket::where('assigned_to', auth('admin')->id())->where('status', 'open')->pluck('subject')->join(', ');

        if (config('app.debug') && staff_has_permission(Permission::MANAGE_SETTINGS)) {
            $heathcheck['yellow'][] = __('admin.dashboard.in_debug');
        }
        if ($notification_error) {
            $heathcheck['yellow'][] = __('admin.dashboard.notification_error', ['message' => $notification_error]);
        }
        if ($tickets_assigned) {
            $heathcheck['blue'][] = __('admin.dashboard.assigned_tickets', ['tickets' => $tickets_assigned]);
        }

        return $heathcheck;
    }
}

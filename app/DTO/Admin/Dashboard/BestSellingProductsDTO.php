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
namespace App\DTO\Admin\Dashboard;

use App\DTO\Admin\Dashboard\Earn\EarnStatisticsItemDTO;
use App\DTO\Admin\Dashboard\Earn\GatewaysSourceDTO;
use App\DTO\Admin\Dashboard\Earn\MonthEarnCanvasDTO;
use App\DTO\Admin\Dashboard\Earn\RelatedBilledDTO;
use App\DTO\Admin\Dashboard\Earn\ServicesStatisticsDTO;
use App\DTO\Admin\Dashboard\Earn\ServicesSubscriptionsCanvasDTO;
use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use App\Models\Billing\Subscription;
use App\Models\Provisioning\Service;
use App\Models\Provisioning\ServiceRenewals;
use App\Models\Store\Product;

class BestSellingProductsDTO
{
    public ?Product $first = null;

    public ?Product $second = null;

    public ?Product $third = null;

    public int $firstCount = 0;

    public int $secondCount = 0;

    public int $thirdCount = 0;

    public function __construct(array $data)
    {
        if (count($data) > 0) {
            $this->first = Product::find($data[0]['related_id']);
            $this->firstCount = $data[0]['count'];
        }
        if (count($data) > 1) {
            $this->second = Product::find($data[1]['related_id']);
            $this->secondCount = $data[1]['count'];
        }
        if (count($data) > 2) {
            $this->third = Product::find($data[2]['related_id']);
            $this->thirdCount = $data[2]['count'];
        }
        if ($this->first == null) {
            $this->first = $this->defaultProduct();
        }
        if ($this->second == null) {
            $this->second = $this->defaultProduct();
        }
        if ($this->third == null) {
            $this->third = $this->defaultProduct();
        }
    }

    public static function getBestProducts()
    {
        $products = InvoiceItem::whereType('service')->groupBy('related_id')->selectRaw('related_id, count(*) as count')->orderBy('count', 'desc')->limit(3)->get();

        return new self($products->toArray());
    }

    public static function getBestProductsLastWeek()
    {
        $products = InvoiceItem::whereType('service')->where('created_at', '>=', now()->subWeek())->groupBy('related_id')->selectRaw('related_id, count(*) as count')->orderBy('count', 'desc')->limit(3)->get();

        return new self($products->toArray());
    }

    public static function getBestProductsLastMonth()
    {
        $products = InvoiceItem::whereType('service')->where('created_at', '>=', now()->subMonth())->groupBy('related_id')->selectRaw('related_id, count(*) as count')->orderBy('count', 'desc')->limit(3)->get();

        return new self($products->toArray());
    }

    public static function getGatewaysSources()
    {
        return new GatewaysSourceDTO(Invoice::groupBy('paymethod')->selectRaw('paymethod, count(id) as count')->selectRaw('sum(subtotal) as subtotal')->orderBy('count', 'desc')->where('paymethod', '!=', 'none')->get());
    }

    public static function getServicesSubscriptions()
    {
        $services = Service::where('expires_at', '>', now())->where('status', 'active')->count();
        $subscriptions = Subscription::where('state', 'active')->count();

        return new ServicesSubscriptionsCanvasDTO($services, $subscriptions);
    }

    public static function getLastOrders()
    {
        $items = InvoiceItem::whereType('service')->orderBy('created_at', 'desc')->where('type', 'service')->with('invoice')->limit(5)->get();

        return $items->map(function ($item) {
            return $item->invoice;
        })->filter(function ($invoice) {
            return $invoice != null;
        });
    }

    public static function getRelatedBilled()
    {
        $data = InvoiceItem::groupBy('type')->selectRaw('type, count(id) as count, SUM(unit_price_ht) as amount')->orderBy('amount', 'desc')->get();

        return new RelatedBilledDTO($data);
    }

    public static function getLastRenewals()
    {
        $items = ServiceRenewals::orderBy('renewed_at', 'desc')->limit(5)->get();

        return $items->filter(function ($item) {
            return $item->service != null && $item->invoice != null;
        })->map(function ($item) {
            return $item->invoice;
        });
    }

    public function getLastWeekLabel()
    {
        return __('admin.dashboard.widgets.best_selling.last_week', ['date' => now()->subWeek()->format('d/m')]);
    }

    public function getLastMonthLabel()
    {
        return __('admin.dashboard.widgets.best_selling.last_month', ['month' => now()->subMonth()->isoFormat('MMMM')]);
    }

    public static function getDetailedProducts()
    {
        return InvoiceItem::whereType('service')->groupBy('related_id')->selectRaw('related_id, count(id) as count')->selectRaw('sum(unit_price_ht) as price')->orderBy('price', 'desc')->paginate(20, '[*]', 'products')->withQueryString();
    }

    public static function compareYear(?int $compareBy = null)
    {
        $current = self::getEarnMonths(now()->year);
        if ($compareBy == null) {
            return new MonthEarnCanvasDTO($current);
        } else {
            $compare = self::getEarnMonths($compareBy);
        }

        return new MonthEarnCanvasDTO($current, $compare, $compareBy);
    }

    private static function getEarnMonths(int $year)
    {
        $months = collect();
        for ($i = 1; $i <= 12; $i++) {
            $months->push([
                'month' => now()->year($year)->month($i)->translatedFormat('F'),
                'year' => now()->year,
                'total' => Invoice::where('paid_at', '>', now()->year($year)->month($i)->startOfMonth())
                    ->where('paid_at', '<', now()->year($year)->month($i)->endOfMonth())
                    ->where('status', 'paid')->sum('total'),
            ]);
        }

        return $months;
    }

    public static function getServicesStatistics(\DateTime $from, int $limit = 10)
    {
        return new ServicesStatisticsDTO($from, $limit);
    }

    private function defaultProduct()
    {
        return new Product([
            'name' => 'No product',
        ]);
    }

    public static function getEarnStatistics(\DateTime $start, ?\DateTime $end)
    {
        $total = Invoice::whereBetween('paid_at', [$start, $end])->where('status', 'paid')->sum('total');
        $tax = Invoice::whereBetween('paid_at', [$start, $end])->where('status', 'paid')->sum('tax');
        $fees = Invoice::whereBetween('paid_at', [$start, $end])->where('status', 'paid')->sum('fees');
        $ca = $total - $tax - $fees;
        $invoices = Invoice::whereBetween('paid_at', [$start, $end])->where('status', 'paid')->count();
        $services = Service::whereBetween('created_at', [$start, $end])->where('status', 'active')->count();

        return [
            new EarnStatisticsItemDTO('bi bi-wallet2', __('admin.dashboard.earn.total_earned'), formatted_price($total), 'primary'),
            new EarnStatisticsItemDTO('bi bi-cash-coin', __('admin.dashboard.earn.total_cash'), formatted_price($ca), 'success'),
            new EarnStatisticsItemDTO('bi bi-currency-dollar', __('admin.dashboard.earn.total_tax'), formatted_price($tax), 'danger'),
            new EarnStatisticsItemDTO('bi bi-tag', __('admin.dashboard.earn.total_fees'), formatted_price($fees), 'info'),
            new EarnStatisticsItemDTO('bi bi-receipt-cutoff', __('admin.dashboard.earn.total_invoices'), $invoices, 'warning'),
            new EarnStatisticsItemDTO('bi bi-boxes', __('admin.dashboard.earn.total_services'), $services, 'secondary'),
        ];
    }

    public static function makeWidgets(bool $isCustom, \DateTime $start, \DateTime $end): array
    {
        $widgets = [];
        $widgets[__('admin.dashboard.earn.all_time')] = self::getEarnStatistics(now()->subYears(10), now());
        $widgets[__('admin.dashboard.earn.current_month')] = self::getEarnStatistics(now()->startOfMonth(), now());
        $widgets[__('admin.dashboard.earn.last_30_days')] = self::getEarnStatistics(now()->subDays(30), now());
        $widgets[__('admin.dashboard.earn.last_7_days')] = self::getEarnStatistics(now()->subDays(7), now());
        $widgets[__('admin.dashboard.earn.today')] = self::getEarnStatistics(now()->subDays(1), now());
        $widgets[__('admin.dashboard.earn.custom')] = $isCustom ? self::getEarnStatistics($start, $end) : [];

        return $widgets;
    }

    public static function bestSellingProducts()
    {
        $dto = \App\DTO\Admin\Dashboard\BestSellingProductsDTO::getBestProducts();
        $week = \App\DTO\Admin\Dashboard\BestSellingProductsDTO::getBestProductsLastWeek();
        $month = \App\DTO\Admin\Dashboard\BestSellingProductsDTO::getBestProductsLastMonth();
        $products = BestSellingProductsDTO::getDetailedProducts();
        $productsNames = Product::whereIn('id', $products->pluck('related_id'))->get()->pluck('name', 'id')->toArray();

        return [
            'dto' => $dto,
            'week' => $week,
            'month' => $month,
            'productsNames' => $productsNames,
            'products' => $products,
            'split' => intdiv($products->count(), 2),
        ];
    }

    public static function getBestCustomers(int $limit = 10)
    {
        $query = Invoice::where('status', 'paid')
            ->groupBy('customer_id')
            ->selectRaw('customer_id, sum(subtotal) as total_subtotal')
            ->orderBy('total_subtotal', 'desc')
            ->with('customer');
        if ($limit == -1) {
            return $query->paginate(20, ['*'], 'best_customers')->withQueryString();
        }

        return $query->limit($limit)->get();
    }
}

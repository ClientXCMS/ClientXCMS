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


namespace App\DTO\Admin\Dashboard\Earn;

use App\Models\Billing\Invoice;
use App\Models\Billing\SubscriptionLog;
use App\Models\Provisioning\Service;
use App\Models\Provisioning\ServiceRenewals;
use App\Models\Store\Pricing;

class ServicesStatisticsDTO
{
    public float $expires_soon;

    public float $actives;

    public int $already_renewed;

    public int $earn;

    public int $subscriptions;

    public int $services;

    public float $averagePeriods;

    public float $averageServicesByCustomer;

    public float $averageServicesPrice;

    public ?Service $maxServicesByCustomer = null;

    public ?Service $maxPrice = null;

    public ?ServiceRenewals $maxPeriods = null;

    public $products;

    public function __construct(\DateTime $from, int $limit)
    {
        $productIds = Service::where('status', 'active')->pluck('product_id');
        $servicesCount = Service::where('status', 'active')->count();
        $ids = ServiceRenewals::where('renewed_at', '>', $from)->pluck('invoice_id');
        $amount = Invoice::where('status', 'paid')->where('paid_at', '>', $from)->whereIn('id', $ids)->sum('subtotal');
        $amountSubscriptions = SubscriptionLog::where('paid_at', '>', $from)->sum('amount');
        $this->expires_soon = Service::where('status', 'active')->where('expires_at', '<=', now()->addDays(setting('days_before_expiration', 7)))->count();
        $this->actives = Service::where('expires_at', '>', $from)->where('status', 'active')->count();
        $this->already_renewed = ServiceRenewals::where('renewed_at', '>', $from)->count();
        $this->earn = $amount;
        $this->subscriptions = $amountSubscriptions;
        $this->services = $servicesCount;
        $this->maxPeriods = $this->maxPeriods();
        $this->averagePeriods = ServiceRenewals::avg('period') ?? 0;
        $this->averageServicesPrice = Pricing::where('related_type', 'service')->orWhere('related_type', 'products')->whereIn('related_id', $productIds)->avg('monthly') ?? 0;
        $this->averageServicesByCustomer = $servicesCount != 0 ? (float) ($servicesCount / Service::where('status', 'active')->groupBy('customer_id')->count()) : 0;
        $this->maxServicesByCustomer = $this->maxServicesByCustomer($productIds);
        $query = Service::where('status', 'active')->groupBy('product_id')->selectRaw('product_id, count(id) as count, avg(renewals) as renewals_avg')->whereNotNull('product_id')->orderBy('count', 'desc')->limit($limit);
        $this->products = $limit != -1 ? $query->paginate($limit, ['*'], 'services')->withQueryString() : $query->limit($limit)->get();
    }

    public function percentage(string $current, string $total): float
    {
        if ($this->$total == 0) {
            return 0;
        }

        return (int) (($this->$current / $this->$total) * 100);
    }

    private function maxServicesByCustomer()
    {
        $service = Service::where('status', 'active')->groupBy('customer_id')->selectRaw('customer_id, count(id) as count, avg(renewals) as renewals_avg')->orderBy('count', 'desc')->first();
        if ($service == null) {
            return null;
        }

        return $service;
    }

    private function maxPeriods()
    {
        $service = ServiceRenewals::selectRaw('service_id, period')->orderBy('period', 'desc')->first();
        if ($service == null) {
            return null;
        }

        return $service;
    }
}

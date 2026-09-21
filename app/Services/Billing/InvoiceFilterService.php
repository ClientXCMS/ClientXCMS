<?php

namespace App\Services\Billing;

use App\Models\Billing\Invoice;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class InvoiceFilterService
{
    public function apply(Builder $query, array $filters, bool $includeDrafts = true): Builder
    {
        if (! $includeDrafts) {
            $query->where('status', '!=', Invoice::STATUS_DRAFT);
        }

        return $query
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->where('created_at', '>=', CarbonImmutable::parse($date)->startOfDay()))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->where('created_at', '<=', CarbonImmutable::parse($date)->endOfDay()))
            ->when($filters['status'] ?? null, function (Builder $query, array $statuses) {
                if (! in_array('all', $statuses, true)) {
                    $query->whereIn('status', $statuses);
                }
            })
            ->when($filters['currency'] ?? null, fn (Builder $query, string $currency) => $query->where('currency', strtoupper($currency)));
    }
}

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
namespace App\Services\Store;

use App\Models\Store\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RecurringService
{
    protected Collection $recurrings;

    public function __construct()
    {
        $this->recurrings = collect();
        $this->setRecurrings();
    }

    public function has(string $key): bool
    {
        return $this->recurrings->has($key);
    }

    public function get(string $key, string $default = 'monthly'): mixed
    {
        return $this->recurrings->get($key, $this->recurrings->get($default));
    }

    private function setRecurrings(): void
    {
        $this->recurrings->put('onetime', ['label' => 'One Time', 'translate' => trans('recurring.onetime'), 'months' => 0, 'unit' => trans('recurring.onetime')]);
        $this->recurrings->put('monthly', ['label' => 'Monthly', 'translate' => trans('recurring.monthly'), 'months' => 1, 'unit' => trans('recurring.month')]);
        $this->recurrings->put('quarterly', ['label' => 'Quarterly', 'translate' => trans('recurring.quarterly'), 'months' => 3, 'unit' => trans('recurring.quarter')]);
        $this->recurrings->put('semiannually', ['label' => 'Semi-Annually', 'translate' => trans('recurring.semiannually'), 'months' => 6, 'unit' => trans('recurring.half-year')]);
        $this->recurrings->put('annually', ['label' => 'Annually', 'translate' => trans('recurring.annually'), 'months' => 12, 'unit' => trans('recurring.year')]);
        $this->recurrings->put('biennially', ['label' => 'Biennially', 'translate' => trans('recurring.biennially'), 'months' => 24, 'unit' => trans('recurring.years'), 'additional' => true]);
        $this->recurrings->put('triennially', ['label' => 'Triennially', 'translate' => trans('recurring.triennially'), 'months' => 36, 'unit' => trans('recurring.years'), 'additional' => true]);
        $this->recurrings->put('weekly', ['label' => 'Weekly', 'translate' => trans('recurring.weekly'), 'months' => 0.5, 'unit' => trans('recurring.week'), 'additional' => true]);
    }

    public function getRecurrings(): Collection
    {
        return $this->recurrings;
    }

    public function getRecurringTypes(): array
    {
        return $this->recurrings->keys()->toArray();
    }

    public function addFrom(Carbon $carbon, string $type, int $additialDays = 0): ?Carbon
    {
        if ($type == 'onetime') {
            return null;
        }
        if ($type == 'weekly') {
            return $carbon->addWeeks(1)->addDays($additialDays);
        }

        return $carbon->addMonths($this->recurrings->get($type)['months'] ?? 1)->addDays($additialDays);
    }

    public function addFromNow(string $type, int $additialDays = 0): ?Carbon
    {
        if ($type == 'onetime') {
            return null;
        }

        return $this->addFrom(now(), $type, $additialDays);
    }
}

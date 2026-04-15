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

namespace App\Services\Store;

use Illuminate\Support\Collection;

class CurrencyService
{
    const KEY_NAME = 'currency';

    protected Collection $currencies;

    public function __construct()
    {
        $this->currencies = collect();
        $this->setCurrencies();
    }

    public function has(string $key): bool
    {
        return $this->currencies->has($key);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->currencies->get($key, $default);
    }

    private function setCurrencies(): void
    {
        $this->currencies->put('USD', ['label' => 'US Dollar', 'translate' => 'currency.usd', 'symbol' => '$', 'code' => 'USD']);
        $this->currencies->put('EUR', ['label' => 'Euro', 'translate' => 'currency.eur', 'symbol' => '€', 'code' => 'EUR']);
        $this->currencies->put('GBP', ['label' => 'Pound Sterling', 'translate' => 'currency.gbp', 'symbol' => '£', 'code' => 'GBP']);
        $this->currencies->put('CAD', ['label' => 'Canadian Dollar', 'translate' => 'currency.cad', 'symbol' => '$', 'code' => 'CAD']);
        $this->currencies->put('CHF', ['label' => 'Swiss Franc', 'translate' => 'currency.chf', 'symbol' => 'CHF', 'code' => 'CHF']);
    }

    public function getCurrencies(): Collection
    {
        return $this->currencies;
    }

    public function getCurrenciesKeys(): array
    {
        return $this->currencies->keys()->toArray();
    }

    public function retrieveCurrency(): string
    {
        return \Session::get(self::KEY_NAME, setting('store_currency', 'EUR'));
    }

    public function setCurrency(string $currency): void
    {
        \Session::put(self::KEY_NAME, $currency);
    }
}

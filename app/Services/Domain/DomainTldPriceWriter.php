<?php

namespace App\Services\Domain;

use App\Models\Store\DomainTld;
use Illuminate\Support\Facades\Validator;

class DomainTldPriceWriter
{
    /** Patch only submitted currency/action/duration cells. A blank price disables that cell. */
    public function write(DomainTld $tld, array $prices): void
    {
        foreach ($prices as $currency => $actions) {
            Validator::make(['currency' => $currency], ['currency' => 'required|regex:/^[A-Za-z]{3}$/'])->validate();
            foreach ($actions as $action => $billings) {
                Validator::make(['action' => $action], ['action' => 'in:register,renew,transfer'])->validate();
                foreach ($billings as $billing => $cell) {
                    Validator::make(compact('billing') + $cell, ['billing' => 'in:annually,biennially,triennially', 'price' => 'nullable|numeric|min:0|max:99999999', 'setup' => 'nullable|numeric|min:0|max:99999999'])->validate();
                    $key = ['currency' => $currency, 'action' => $action, 'billing' => $billing];
                    if (! array_key_exists('price', $cell)) {
                        continue;
                    }
                    if ($cell['price'] === null || $cell['price'] === '') {
                        $tld->prices()->where($key)->delete();

                        continue;
                    }
                    $price = $tld->prices()->firstOrNew($key);
                    $price->price = $cell['price'];
                    if (array_key_exists('setup', $cell)) {
                        $price->setup = $cell['setup'] ?? 0;
                    } elseif (! $price->exists) {
                        $price->setup = 0;
                    }
                    $price->save();
                }
            }
        }
    }
}

<?php

namespace App\Services\Store;

use App\DTO\Store\ConfigOptionDTO;
use App\Models\Billing\ConfigOption;
use App\Models\Store\Product;
use App\Services\Store\RecurringService;
use App\Services\Store\TaxesService;

class ProductConfigurationPricingService
{
    public function preview(Product $product, string $billing, string $currency, array $optionsInput = []): array
    {
        $price = $product->getPriceByCurrency($currency, $billing);
        $options = $this->mapOptions($product, $billing, $optionsInput);

        $optionTotals = $this->computeOptionTotals($options, $currency, $billing);

        $recurringHt = $price->recurringPayment() + $optionTotals['recurring'];
        $onetimeHt   = $price->onetimePayment() + $optionTotals['onetime'];
        $setupHt     = $price->setupHT() + $optionTotals['setup'];

        $firstPaymentHt = $price->firstPayment() + $optionTotals['first_payment'];
        $taxAmount      = TaxesService::getVatPrice($firstPaymentHt);
        $totalTtc       = $firstPaymentHt + $taxAmount;

        return [
            'currency'     => $currency,
            'billing'      => $billing,
            'mode'         => setting('store_mode_tax', TaxesService::MODE_TAX_EXCLUDED),
            'display_mode' => setting('display_product_price', TaxesService::PRICE_TTC),
            'tax_rate'     => $price->taxRate(),
            'totals'       => [
                'recurring_ht'      => $this->round($recurringHt),
                'onetime_ht'        => $this->round($onetimeHt),
                'setup_ht'          => $this->round($setupHt),
                'first_payment_ht'  => $this->round($firstPaymentHt),
                'tax'               => $this->round($taxAmount),
                'total'             => $this->round($totalTtc),
            ],
            'options'   => array_values($this->formatOptions($options, $currency, $billing)),
            'formatted' => [
                'recurring' => $this->formatMoney($recurringHt, $currency),
                'onetime'   => $this->formatMoney($onetimeHt, $currency),
                'setup'     => $this->formatMoney($setupHt, $currency),
                'subtotal'  => $this->formatMoney($firstPaymentHt, $currency),
                'taxes'     => formatted_price($taxAmount, $currency),
                'total'     => formatted_price($totalTtc, $currency),
            ],
        ];
    }

    private function formatMoney(float $amountHt, string $currency): string
    {
        return formatted_price($amountHt, $currency);
    }

    private function round(float $amount): float
    {
        return round($amount, 2);
    }

    private function mapOptions(Product $product, string $billing, array $optionsInput): array
    {
        $configOptions = $product->configoptions()->orderBy('sort_order')->get();
        $recurringService = app(RecurringService::class);

        $options = [];
        foreach ($configOptions as $configOption) {
            $value = $optionsInput[$configOption->key] ?? null;
            $normalizedValue = $this->normalizeOptionValue($configOption, $value);

            $options[$configOption->key] = [
                'config' => $configOption,
                'value'  => $normalizedValue,
                'dto'    => $normalizedValue !== null
                    ? new ConfigOptionDTO($configOption, $normalizedValue, $recurringService->addFromNow($billing))
                    : null,
            ];
        }

        return $options;
    }

    private function normalizeOptionValue(ConfigOption $configOption, $value)
    {
        if ($configOption->type === ConfigOption::TYPE_CHECKBOX) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : null;
        }

        if ($value === '' || $value === '0') {
            return null;
        }

        return $value;
    }

    private function computeOptionTotals(array $options, string $currency, string $billing): array
    {
        $totals = [
            'recurring'     => 0.0,
            'setup'         => 0.0,
            'onetime'       => 0.0,
            'first_payment' => 0.0,
        ];

        foreach ($options as $option) {
            if (! $option['dto'] instanceof ConfigOptionDTO) {
                continue;
            }
            $dto = $option['dto'];
            $totals['recurring']     += $dto->recurringPayment($currency, $billing);
            $totals['setup']         += $dto->setup($currency, $billing);
            $totals['onetime']       += $dto->onetimePayment($currency, $billing);
            $totals['first_payment'] += $dto->subtotal($currency, $billing);
        }

        return $totals;
    }

    private function formatOptions(array $options, string $currency, string $billing): array
    {
        return collect($options)->map(function (array $option) use ($currency, $billing) {
            if (! $option['dto'] instanceof ConfigOptionDTO) {
                return [
                    'key'               => $option['config']->key,
                    'label'             => $option['config']->name,
                    'amount_ht'         => 0.0,
                    'recurring_ht'      => 0.0,
                    'setup_ht'          => 0.0,
                    'onetime_ht'        => 0.0,
                    'formatted'         => formatted_price(0, $currency),
                ];
            }

            /** @var ConfigOptionDTO $dto */
            $dto = $option['dto'];
            $amountHt   = $dto->subtotal($currency, $billing);
            $recurring  = $dto->recurringPayment($currency, $billing);
            $setup      = $dto->setup($currency, $billing);
            $onetime    = $dto->onetimePayment($currency, $billing);

            return [
                'key'               => $option['config']->key,
                'label'             => $dto->formattedName(false),
                'amount_ht'         => $this->round($amountHt),
                'recurring_ht'      => $this->round($recurring),
                'setup_ht'          => $this->round($setup),
                'onetime_ht'        => $this->round($onetime),
                'formatted'         => formatted_price($amountHt, $currency),
            ];
        })->toArray();
    }
}

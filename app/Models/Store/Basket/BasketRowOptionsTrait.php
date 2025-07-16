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
namespace App\Models\Store\Basket;

use App\DTO\Store\ConfigOptionDTO;
use App\Models\Billing\ConfigOption;
use App\Services\Store\RecurringService;

trait BasketRowOptionsTrait
{
    public function getOptions(): array
    {
        return $this->options;
    }

    public static function bootBasketRowOptionsTrait()
    {
        static::retrieved(function (BasketRow $model) {
            $options = [];
            $configoptions = $model->product ? $model->product->configoptions : null;
            if (! $configoptions) {
                return;
            }
            $billing = $model->billing;
            foreach ($model->options as $key => $value) {
                /** @var ConfigOption $option */
                $option = $configoptions->where('key', $key)->first();
                if (! $option) {
                    continue;
                }
                if (($first = $option->getFirstPrice())->recurring != $billing) {
                    $billing = $first->recurring;
                }
                $options[] = new ConfigOptionDTO($option, $value, app(RecurringService::class)->addFromNow($billing));
            }
            $model->options = $options;
        });
    }

    public function addOption(string $key, $value): void
    {
        $options = $this->options;
        $options[$key] = $value;
        $this->update(['options' => json_encode($options)]);
    }

    public function saveOptions(array $options, $configoptions): void
    {
        $keys = collect($configoptions)->map(function ($configoption) {
            return $configoption->key;
        })->toArray();
        $saved = [];
        foreach ($options as $key => $value) {
            if ($value == null || ! in_array($key, $keys) || $value == '' || $value == '0') {
                unset($saved[$key]);
            } else {
                $saved[$key] = $value;
            }
        }
        $this->update(['options' => json_encode($saved)]);
    }

    public function optionRecurringPayment(): float
    {
        return collect($this->options)->reduce(function (float $total, ConfigOptionDTO $DTO) {
            return $total + $DTO->recurringPayment($this->currency, $this->billing);
        }, 0);
    }

    public function optionsAmountBillable():float
    {
        return collect($this->options)->reduce(function (float $total, ConfigOptionDTO $DTO) {
            return $total + $DTO->billableAmount($this->currency, $this->billing);
        }, 0);
    }

    public function optionSetup(): float
    {
        return collect($this->options)->reduce(function (float $total, ConfigOptionDTO $DTO) {
            return $total + $DTO->setup($this->currency, $this->billing);
        }, 0);
    }

    public function optionOnetimePayment(): float
    {
        return collect($this->options)->reduce(function (float $total, ConfigOptionDTO $DTO) {
            return $total + $DTO->onetimePayment($this->currency, $this->billing);
        }, 0);
    }

    public function optionTax(): float
    {
        return collect($this->options)->reduce(function (float $total, ConfigOptionDTO $DTO) {
            return $total + $DTO->tax($this->currency, $this->billing);
        }, 0);
    }

    public function optionsFormattedName(bool $short = true)
    {
        return collect($this->options)->map(function (ConfigOptionDTO $DTO) use ($short) {
            return $DTO->formattedName($short);
        });
    }

    public function recurringPaymentWithoutCouponWithoutOptions(bool $withQuantity = true, ?string $billing = null): float
    {
        $this->enableCoupon(false);
        if ($this->billing == 'onetime') {
            return 0;
        }
        if (! $withQuantity) {
            $recurringPayment = $this->product->getPriceByCurrency($this->currency, $billing ?? $this->billing)->recurringPayment();
        } else {
            $recurringPayment = $this->product->getPriceByCurrency($this->currency, $billing ?? $this->billing)->recurringPayment() * $this->quantity;
        }

        return $this->applyCoupon($recurringPayment, self::PRICE);
    }

    public function onetimePaymentWithoutCouponWithoutOptions(bool $withQuantity = true, ?string $billing = null): float
    {
        $this->enableCoupon(false);
        if ($this->billing != 'onetime') {
            return 0;
        }
        if (! $withQuantity) {
            $onetimePayment = $this->product->getPriceByCurrency($this->currency, $billing ?? $this->billing)->onetimePayment();
        } else {
            $onetimePayment = $this->product->getPriceByCurrency($this->currency, $billing ?? $this->billing)->onetimePayment() * $this->quantity;
        }

        return $this->applyCoupon($onetimePayment, self::PRICE);
    }

    public function setupWithoutCouponWithoutOptions(bool $withQuantity = true, ?string $billing = null): float
    {
        $this->enableCoupon(false);
        if (! $withQuantity) {
            $setup = $this->product->getPriceByCurrency($this->currency, $billing ?? $this->billing)->setup;
        } else {
            $setup = $this->product->getPriceByCurrency($this->currency, $billing ?? $this->billing)->setup * $this->quantity;
        }

        return $this->applyCoupon($setup, self::SETUP_FEES);
    }

    public function onetimePaymentWithOptions(bool $withQuantity = true, ?string $billing = null): float
    {
        $onetimePayment = $this->onetimePaymentWithoutCouponWithoutOptions($withQuantity, $billing);
        $onetimePayment += $this->optionOnetimePayment();

        return $onetimePayment;
    }

    public function setupWithOptions(bool $withQuantity = true, ?string $billing = null): float
    {
        $setup = $this->setupWithoutCouponWithoutOptions($withQuantity, $billing);
        $setup += $this->optionSetup();

        return $setup;
    }

    public function recurringPaymentWithOptions(bool $withQuantity = true, ?string $billing = null): float
    {
        $recurringPayment = $this->recurringPaymentWithoutCouponWithoutOptions($withQuantity, $billing);
        $recurringPayment += $this->optionRecurringPayment();

        return $recurringPayment;
    }
}

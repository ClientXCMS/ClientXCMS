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
namespace App\DTO\Store;

use App\Models\Billing\ConfigOption;
use App\Models\Provisioning\Service;
use App\Services\Store\PricingService;
use App\Services\Store\RecurringService;
use Carbon\Carbon;

class ConfigOptionDTO
{
    public ConfigOption $option;

    public mixed $value = null;

    private bool $addSetup;

    private ?\Carbon\Carbon $expiresAt;

    /**
     * @param  null  $value
     * @param  bool  $addSetup  - Permet de ne pas ajouter le setup dans le calcul du prix par exemple pour les renouvellements de services
     */
    public function __construct(ConfigOption $option, $value = null, ?\Carbon\Carbon $expiresAt = null, bool $addSetup = true)
    {
        $this->option = $option;
        $this->value = $value;
        $this->addSetup = $addSetup;
        $this->expiresAt = $expiresAt;
    }

    public function getExpiresAt(string $currency, string $billing): ?\DateTime
    {
        $isOnetime = $this->onetimePayment($currency, $billing) > 0 && $this->recurringPayment($currency, $billing) == 0;
        if ($isOnetime) {
            return null;
        }

        return $this->expiresAt;
    }

    public function render()
    {
        $view = 'front.store.configoptions.'.$this->option->type;
        $options = [];
        $options_attributes = [];
        if ($this->option->type == ConfigOption::TYPE_DROPDOWN) {
            $options = $this->option->options;
            $options = $options->mapWithKeys(function ($option) {
                return [$option->value => $option->friendly_name];
            });
            $options_attributes = $this->option->options->mapWithKeys(function ($option) {
                return [$option->value => [
                    'data-dropdown-id' => $option->id,
                    'data-title' => $option->friendly_name,
                ]];
            });
        }
        if ($this->option->type == ConfigOption::TYPE_RADIO) {
            $options = $this->option->options;
        }
        if ($this->value === null && ($this->option->type == ConfigOption::TYPE_RADIO || $this->option->type == ConfigOption::TYPE_DROPDOWN)) {
            $this->value = $this->option->options->first()?->value;
        }

        return view($view, [
            'option' => $this->option,
            'value' => $this->value,
            'options' => $options,
            'pricing' => $this->pricing(),
            'options_attributes' => $options_attributes,
        ])->render();
    }

    public function pricing(?string $currency = null)
    {
        if ($this->option->type == ConfigOption::TYPE_CHECKBOX || $this->option->type == ConfigOption::TYPE_RADIO || $this->option->type == ConfigOption::TYPE_DROPDOWN) {
            $option = $this->option->options->firstWhere('value', $this->value);
            $pricing = $option ? PricingService::for($option->id, 'config_options_option') : null;
            if ($pricing) {
                if ($currency) {
                    return $pricing->where('currency', $currency)->first();
                }

                return $pricing->first();
            }
        } else {
            $pricing = PricingService::for($this->option->id, 'config_option');
            if ($pricing) {
                if ($currency) {
                    return $pricing->where('currency', $currency)->first();
                }

                return $pricing->first();
            }
        }
    }

    public function recurringPayment(string $currency, string $recurring, bool $quantity = true)
    {
        $quantity = $quantity ? $this->quantity() : 1;
        if ($this->option->type == ConfigOption::TYPE_RADIO || $this->option->type == ConfigOption::TYPE_DROPDOWN) {
            $selected = $this->option->options->firstWhere('value', $this->value);
            if ($selected) {
                return $selected->getPriceByCurrency($currency, $recurring)->recurringPayment();
            }
        }
        if ($this->option->type == ConfigOption::TYPE_SLIDER) {
            return ($this->option->getPriceByCurrency($currency, $recurring)->recurringPayment() / $this->option->step) * $quantity;
        }

        return $this->option->getPriceByCurrency($currency, $recurring)->recurringPayment() * $quantity;
    }

    public function setup(string $currency, string $recurring, bool $quantity = true)
    {
        if (! $this->addSetup) {
            return 0;
        }
        $quantity = $quantity ? $this->quantity() : 1;
        if ($this->option->type == ConfigOption::TYPE_RADIO || $this->option->type == ConfigOption::TYPE_DROPDOWN) {
            $selected = $this->option->options->firstWhere('value', $this->value);
            if ($selected) {
                return $selected->getPriceByCurrency($currency, $recurring)->setup();
            }
        }
        if ($this->option->type == ConfigOption::TYPE_SLIDER) {
            return ($this->option->getPriceByCurrency($currency, $recurring)->setup() / $this->option->step) * $quantity;
        }

        return $this->option->getPriceByCurrency($currency, $recurring)->setup() * $quantity;
    }

    public function onetimePayment(string $currency, string $recurring, bool $quantity = true)
    {
        $quantity = $quantity ? $this->quantity() : 1;
        if ($this->option->type == ConfigOption::TYPE_RADIO || $this->option->type == ConfigOption::TYPE_DROPDOWN) {
            $selected = $this->option->options->firstWhere('value', $this->value);
            if ($selected) {
                return $selected->getPriceByCurrency($currency, $recurring)->onetimePayment();
            }
        }
        if ($this->option->type == ConfigOption::TYPE_SLIDER) {
            return ($this->option->getPriceByCurrency($currency, $recurring)->onetimePayment() / $this->option->step) * $quantity;
        }

        return $this->option->getPriceByCurrency($currency, $recurring)->onetimePayment() * $quantity;
    }

    public function subtotal(string $currency, string $recurring, bool $quantity = true)
    {
        $quantity = $quantity ? $this->quantity() : 1;
        if ($this->option->type == ConfigOption::TYPE_RADIO || $this->option->type == ConfigOption::TYPE_DROPDOWN) {
            $selected = $this->option->options->firstWhere('value', $this->value);
            if ($selected) {
                return $selected->getPriceByCurrency($currency, $recurring)->firstPayment();
            }
        }
        if ($this->option->type == ConfigOption::TYPE_SLIDER) {
            return ($this->option->getPriceByCurrency($currency, $recurring)->firstPayment() / $this->option->step) * $quantity;
        }

        return $this->option->getPriceByCurrency($currency, $recurring)->firstPayment() * $quantity;
    }

    public function billableAmount(string $currency, string $recurring, bool $quantity = true)
    {
        $quantity = $quantity ? $this->quantity() : 1;
        if ($this->option->type == ConfigOption::TYPE_RADIO || $this->option->type == ConfigOption::TYPE_DROPDOWN) {
            $selected = $this->option->options->firstWhere('value', $this->value);
            if ($selected) {
                return $selected->getPriceByCurrency($currency, $recurring)->billableAmount();
            }
        }
        if ($this->option->type == ConfigOption::TYPE_SLIDER) {
            return ($this->option->getPriceByCurrency($currency, $recurring)->billableAmount() / $this->option->step) * $quantity;
        }

        return $this->option->getPriceByCurrency($currency, $recurring)->billableAmount() * $quantity;
    }

    public function tax(string $currency, string $recurring, bool $quantity = true)
    {
        $quantity = $quantity ? $this->quantity() : 1;
        if ($this->option->type == ConfigOption::TYPE_RADIO || $this->option->type == ConfigOption::TYPE_DROPDOWN) {
            $selected = $this->option->options->firstWhere('value', $this->value);
            if ($selected) {
                return $selected->getPriceByCurrency($currency, $recurring)->tax();
            }
        }
        if ($this->option->type == ConfigOption::TYPE_SLIDER) {
            return ($this->option->getPriceByCurrency($currency, $recurring)->tax() / $this->option->step) * $quantity;
        }

        return $this->option->getPriceByCurrency($currency, $recurring)->tax() * $quantity;
    }

    public function quantity()
    {
        return $this->option->type == 'slider' ? (int) $this->value : 1;
    }

    public function formattedName(bool $short = true)
    {
        if ($this->option->unit && $this->option->type == ConfigOption::TYPE_SLIDER) {
            if ($short) {
                return $this->value.' '.$this->option->unit.'';
            }

            return $this->option->name.' ('.$this->value.' '.$this->option->unit.')';
        }
        if ($this->option->type == ConfigOption::TYPE_RADIO || $this->option->type == ConfigOption::TYPE_DROPDOWN) {
            $selected = $this->option->options->firstWhere('value', $this->value);
            if ($selected) {
                if ($short) {
                    return $selected->friendly_name;
                }

                return $this->option->name.' : '.$selected->friendly_name;
            }
        }
        if ($this->option->type == ConfigOption::TYPE_CHECKBOX) {
            if ($short) {
                return $this->option->name;
            }

            return $this->option->name.' : '.($this->value == 'true' ? __('global.yes') : __('global.no'));
        }

        if ($this->option->type == ConfigOption::TYPE_NUMBER || $this->option->type == ConfigOption::TYPE_TEXT) {
            if ($short) {
                return $this->value.' '.$this->option->unit;
            }

            return $this->option->name.' : '.$this->value.' '.$this->option->unit;
        }

        return $this->option->name;
    }

    public function getBillingName(string $currency, string $billing, ?\DateTime $expiresAt = null)
    {
        if ($this->recurringPayment($currency, $billing) == 0) {
            return $this->option->name;
        }
        $current = $this->expiresAt ?? Carbon::now();
        $expiresAt = $expiresAt ?? app(RecurringService::class)->addFrom(clone $current, $billing);

        return "{$this->option->name} ({$current->format('d/m/y')} - {$expiresAt->format('d/m/y')})";
    }

    public function getBillingDescription()
    {
        if ($this->option->type == ConfigOption::TYPE_RADIO || $this->option->type == ConfigOption::TYPE_DROPDOWN) {
            $selected = $this->option->options->firstWhere('value', $this->value);
            if ($selected) {
                return $selected->friendly_name;
            }
        }
        if ($this->option->type == ConfigOption::TYPE_CHECKBOX) {
            return $this->option->name;
        }
        if ($this->option->type == ConfigOption::TYPE_NUMBER || $this->option->type == ConfigOption::TYPE_TEXT) {
            return $this->value;
        }
        if ($this->option->type == ConfigOption::TYPE_SLIDER) {
            return $this->value.' '.$this->option->unit;
        }

        return '';

    }

    public function validate()
    {
        $rules = $this->option->rules;
        if ($rules && ! empty($rules)) {
            $rules = [$rules];
        } else {
            $rules = ['nullable'];
        }

        if ($this->option->required) {
            $rules[] = 'required';
        }
        if ($this->option->type === ConfigOption::TYPE_NUMBER || $this->option->type === ConfigOption::TYPE_SLIDER) {
            $rules[] = 'numeric';
            if ($this->option->min_value) {
                $rules[] = 'min:'.$this->option->min_value;
            }
            if ($this->option->max_value) {
                $rules[] = 'max:'.$this->option->max_value;
            }
        }

        return $rules;
    }

    public function total(string $currency, string $billing)
    {
        return $this->recurringPayment($currency, $billing) + $this->setup($currency, $billing) + $this->tax($currency, $billing);
    }

    public function data(string $currency, string $billing, ?\Carbon\Carbon $expiresAt = null): array
    {
        $isOnetime = $this->onetimePayment($currency, $billing) > 0 && $this->recurringPayment($currency, $billing) == 0;
        if ($isOnetime) {
            $expiresAt = null;
        } else {
            $expiresAt = $expiresAt ?? app(RecurringService::class)->addFrom(clone $this->expiresAt, $billing)->format('d-m-y H:i');
        }

        return [
            'value' => $this->value,
            'key' => $this->option->key,
            'months' => $isOnetime ? 0 : app(RecurringService::class)->get($billing)['months'],
            'expires_at' => $expiresAt,
        ];
    }

    public function needRenewal(Service $service)
    {
        if ($service->expires_at != null && $this->expiresAt != null) {
            return $this->expiresAt->isSameDay($service->expires_at);
        }

        return $this->expiresAt && ($this->expiresAt->isCurrentMonth() || $this->expiresAt->isNextMonth());
    }
}

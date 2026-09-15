<?php

namespace Tests\Unit\DTO\Store;

use App\Models\Billing\ConfigOption;
use App\Models\Billing\ConfigOptionsOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ConfigOptionValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_dropdown_only_accepts_the_values_it_offers(): void
    {
        $option = $this->option(ConfigOption::TYPE_DROPDOWN, ['required' => true]);
        $this->offer($option, '4go');
        $this->offer($option, '8go');

        $rules = ['value' => $option->fresh()->validate()];

        $this->assertTrue(Validator::make(['value' => '4go'], $rules)->passes());
        $this->assertFalse(
            Validator::make(['value' => '128go'], $rules)->passes(),
            'a value that is not offered must be refused: it matches no tariff and falls back to a free parent price'
        );
    }

    public function test_a_radio_only_accepts_the_values_it_offers(): void
    {
        $option = $this->option(ConfigOption::TYPE_RADIO, ['required' => true]);
        $this->offer($option, 'weekly');

        $rules = ['value' => $option->fresh()->validate()];

        $this->assertTrue(Validator::make(['value' => 'weekly'], $rules)->passes());
        $this->assertFalse(Validator::make(['value' => 'never'], $rules)->passes());
    }

    public function test_an_optional_dropdown_still_accepts_an_empty_choice(): void
    {
        $option = $this->option(ConfigOption::TYPE_DROPDOWN, ['required' => false]);
        $this->offer($option, '4go');

        $rules = ['value' => $option->fresh()->validate()];

        $this->assertTrue(Validator::make(['value' => ''], $rules)->passes());
        $this->assertFalse(Validator::make(['value' => '128go'], $rules)->passes());
    }

    public function test_a_number_option_refuses_a_value_below_a_zero_minimum(): void
    {
        $option = $this->option(ConfigOption::TYPE_NUMBER, ['min_value' => 0, 'max_value' => 100]);

        $rules = ['value' => $option->validate()];

        $this->assertTrue(Validator::make(['value' => 5], $rules)->passes());
        $this->assertFalse(
            Validator::make(['value' => -100], $rules)->passes(),
            'a minimum of zero is still a minimum: a negative quantity must be refused'
        );
    }

    private function option(string $type, array $attributes = []): ConfigOption
    {
        $option = new ConfigOption;
        $option->name = 'Test option';
        $option->key = 'test_option';
        $option->type = $type;
        $option->hidden = 0;
        $option->fill($attributes);
        $option->save();

        return $option;
    }

    private function offer(ConfigOption $option, string $value): void
    {
        $offered = new ConfigOptionsOption;
        $offered->config_option_id = $option->id;
        $offered->value = $value;
        $offered->friendly_name = strtoupper($value);
        $offered->hidden = false;
        $offered->save();
    }
}

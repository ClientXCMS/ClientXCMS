<?php

namespace Database\Seeders;

use App\Models\Billing\ConfigOption;
use App\Models\Store\Pricing;
use Illuminate\Database\Seeder;

class ConfigOptionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedText();
        $this->seedRadio();
        $this->seedCheckbox();
        $this->seedNumber();
        $this->seedSlider();
        $this->seedDropdown();
    }

    private function seedText()
    {
        $configoption = ConfigOption::create([
            'key' => 'custom_text',
            'type' => 'text',
            'name' => 'Custom text',
            'rules' => 'max:255',
            'required' => false,
        ]);
        $configoption->products()->attach(1);
        Pricing::createFromArray([
            'pricing' => [
                'monthly' => ['price' => 20, 'setup' => 0],
            ],
        ], $configoption->id, 'config_option');
    }

    private function seedRadio()
    {
        $configoption = ConfigOption::create([
            'key' => 'custom_radio',
            'type' => 'radio',
            'name' => 'Custom radio',
            'rules' => null,
            'required' => false,
        ]);
        $configoption->products()->attach(1);
        $option = $configoption->options()->create([
            'friendly_name' => 'Option 1',
            'value' => 'option1',
            'sort_order' => 0,
            'hidden' => false,
        ]);

        Pricing::createFromArray([
            'pricing' => [
                'monthly' => ['price' => 20, 'setup' => 0],
            ],
        ], $option->id, 'config_options_option');
        $option = $configoption->options()->create([
            'friendly_name' => 'Option 2',
            'value' => 'option2',
            'sort_order' => 1,
            'hidden' => false,
        ]);
        Pricing::createFromArray([
            'pricing' => [
                'monthly' => ['price' => 20, 'setup' => 0],
            ],
        ], $option->id, 'config_options_option');
    }

    private function seedCheckbox()
    {
        $configoption = ConfigOption::create([
            'key' => 'custom_checkbox',
            'type' => 'checkbox',
            'name' => 'Custom checkbox',
            'rules' => null,
            'required' => false,
        ]);
        $configoption->products()->attach(1);
        Pricing::createFromArray([
            'pricing' => [
                'monthly' => ['price' => 20, 'setup' => 0],
            ],
        ], $configoption->id, 'config_option');
    }

    private function seedNumber()
    {
        $configoption = ConfigOption::create([
            'key' => 'custom_number',
            'type' => 'number',
            'name' => 'Custom number',
            'rules' => '',
            'min_value' => 0,
            'max_value' => 100,
            'required' => false,
        ]);
        $configoption->products()->attach(1);
        Pricing::createFromArray([
            'pricing' => [
                'monthly' => ['price' => 20, 'setup' => 0],
            ],
        ], $configoption->id, 'config_option');
    }

    private function seedSlider()
    {
        $configoption = ConfigOption::create([
            'key' => 'custom_slider',
            'type' => 'slider',
            'name' => 'Custom slider',
            'rules' => '',
            'min_value' => 0,
            'max_value' => 100,
            'step' => 1,
            'required' => false,
        ]);
        $configoption->products()->attach(1);
        Pricing::createFromArray([
            'pricing' => [
                'monthly' => ['price' => 20, 'setup' => 0],
            ],
        ], $configoption->id, 'config_option');
    }

    private function seedDropdown()
    {
        $configoption = ConfigOption::create([
            'key' => 'custom_dropdown',
            'type' => 'dropdown',
            'name' => 'Custom dropdown',
            'rules' => '',
            'required' => false,
        ]);
        $configoption->products()->attach(1);
        $option = $configoption->options()->create([
            'friendly_name' => 'Option 1',
            'value' => 'option1',
            'sort_order' => 0,
            'hidden' => false,
        ]);
        Pricing::createFromArray([
            'pricing' => [
                'monthly' => ['price' => 20, 'setup' => 0],
            ],
        ], $option->id, 'config_options_option');
        $option = $configoption->options()->create([
            'friendly_name' => 'Option 2',
            'value' => 'option2',
            'sort_order' => 1,
            'hidden' => false,
        ]);
        Pricing::createFromArray([
            'pricing' => [
                'monthly' => ['price' => 20, 'setup' => 0],
            ],
        ], $option->id, 'config_options_option');
    }
}

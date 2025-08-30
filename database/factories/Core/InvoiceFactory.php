<?php

namespace Database\Factories\Core;

use App\Models\Account\Customer;
use App\Models\Billing\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = Customer::first()->id;
        $end = $start + 10;
        $subtotal = 1;
        $tax = $subtotal * 0.2;

        return [
            'customer_id' => $this->faker->numberBetween($start, $end),
            'due_date' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'status' => 'pending',
            'total' => $subtotal + $tax,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'setupfees' => 0,
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
            'external_id' => $this->faker->uuid(),
            'notes' => $this->faker->text(),
        ];
    }

    public function modelName()
    {
        return Invoice::class;
    }
}

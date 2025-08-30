<?php

namespace Database\Factories\Core;

use App\Models\Account\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account\Customer>
 */
class CustomerFactory extends Factory
{
    protected static ?string $password;

    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $phone = '06'.random_int(10000000, 99999999);

        return [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'country' => 'FR',
            'phone' => $phone,
            'address' => fake()->streetAddress(),
            'address2' => fake()->secondaryAddress(),
            'city' => fake()->city(),
            'region' => fake()->state(),
            'zipcode' => '75000',
            'balance' => 1000,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= 'password',
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function modelName()
    {
        return Customer::class;
    }
}

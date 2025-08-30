<?php

namespace Database\Factories\Admin;

use App\Models\Admin\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class AdminFactory extends Factory
{
    protected static string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail,
            'username' => $this->faker->unique()->userName,
            'firstname' => $this->faker->firstName,
            'password' => static::$password ??= 'password',
            'lastname' => $this->faker->lastName,
            'role_id' => Role::first() ? Role::first()->id : Role::factory()->create()->id,
        ];
    }
}

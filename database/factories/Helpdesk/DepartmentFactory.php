<?php

namespace Database\Factories\Helpdesk;

use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
    protected $model = \App\Models\Helpdesk\SupportDepartment::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'description' => $this->faker->sentence,
            'icon' => 'bi bi-question-circle',
        ];
    }
}

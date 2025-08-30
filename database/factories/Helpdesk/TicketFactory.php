<?php

namespace Database\Factories\Helpdesk;

use App\Models\Account\Customer;
use App\Models\Helpdesk\SupportDepartment;
use App\Models\Helpdesk\SupportTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = SupportTicket::class;

    public function definition()
    {
        $department = SupportDepartment::first();
        if (! $department) {
            $department = SupportDepartment::factory()->create();
        }

        return [
            'subject' => $this->faker->sentence,
            'customer_id' => Customer::first()->id,
            'status' => 'open',
            'priority' => 'low',
            'department_id' => $department->id,
            'uuid' => $this->faker->uuid,
        ];
    }
}

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

<?php

namespace Database\Seeders;

use App\Models\Helpdesk\SupportDepartment;
use Illuminate\Database\Seeder;

class SupportDepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (SupportDepartment::count() !== 0) {
            return;
        }
        $departments = [
            [
                'name' => __('helpdesk.support.departmentsseeder.general.name'),
                'description' => __('helpdesk.support.departmentsseeder.general.description'),
                'icon' => 'bi bi-question-circle',
            ],
            [
                'name' => __('helpdesk.support.departmentsseeder.billing.name'),
                'description' => __('helpdesk.support.departmentsseeder.billing.description'),
                'icon' => 'bi bi-credit-card',
            ],
            [
                'name' => __('helpdesk.support.departmentsseeder.technical.name'),
                'description' => __('helpdesk.support.departmentsseeder.technical.description'),
                'icon' => 'bi bi-tools',
            ],
            [
                'name' => __('helpdesk.support.departmentsseeder.sales.name'),
                'description' => __('helpdesk.support.departmentsseeder.sales.description'),
                'icon' => 'bi bi-cart',
            ],
        ];
        SupportDepartment::insert($departments);
    }
}

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
        // if (SupportDepartment::count() !== 0) {
        //     return;
        // }
        $departments = [
            [
                'name' => __('install.departmentsseeder.general.name'),
                'description' => __('install.departmentsseeder.general.description'),
                'icon' => 'bi bi-question-circle',
            ],
            [
                'name' => __('install.departmentsseeder.billing.name'),
                'description' => __('install.departmentsseeder.billing.description'),
                'icon' => 'bi bi-credit-card',
            ],
            [
                'name' => __('install.departmentsseeder.technical.name'),
                'description' => __('install.departmentsseeder.technical.description'),
                'icon' => 'bi bi-tools',
            ],
            [
                'name' => __('install.departmentsseeder.sales.name'),
                'description' => __('install.departmentsseeder.sales.description'),
                'icon' => 'bi bi-cart',
            ],
        ];
        dd($departments);
        SupportDepartment::insert($departments);
    }
}

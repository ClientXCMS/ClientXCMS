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
namespace Tests\Feature\Admin;

use App\Models\Account\Customer;
use App\Models\Account\EmailMessage;
use Database\Seeders\AdminSeeder;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailControllerTest extends TestCase
{
    const API_URL = 'admin/emails';

    use RefreshDatabase;

    public function test_admin_email_index(): void
    {
        $this->seed(AdminSeeder::class);
        $customer = Customer::factory()->create();
        $this->seed(EmailTemplateSeeder::class);
        $emailMessage = EmailMessage::factory()->create();
        $response = $this->performAdminAction('get', self::API_URL);
        $response->assertStatus(200);
    }

    public function test_admin_email_show(): void
    {
        $this->seed(AdminSeeder::class);
        $customer = Customer::factory()->create();
        $this->seed(EmailTemplateSeeder::class);
        $emailMessage = EmailMessage::factory()->create();
        $admin = \App\Models\Admin\Admin::first();
        $response = $this->performAdminAction('get', self::API_URL.'/'.$emailMessage->id);
        $response->assertStatus(200);
    }
}

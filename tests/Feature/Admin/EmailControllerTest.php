<?php

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

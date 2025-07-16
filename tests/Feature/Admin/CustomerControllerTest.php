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
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerControllerTest extends TestCase
{
    const API_URL = '/admin/customers';

    use RefreshDatabase;

    public function test_admin_customer_index(): void
    {
        $response = $this->performAdminAction('GET', self::API_URL);
        $response->assertStatus(200);
    }

    public function test_admin_customer_index_with_invalid_permission(): void
    {
        $response = $this->performAdminAction('GET', self::API_URL, [], ['admin.manage_products']);
        $response->assertStatus(403);
    }

    public function test_admin_customer_search(): void
    {
        Customer::factory(15)->create([]);
        $firstCustomer = Customer::first();
        $firstCustomer->update(['firstname' => 'Martin']);
        $response = $this->performAdminAction('GET', self::API_URL.'?filter[firstname]='.$firstCustomer->firstname);
        $response->assertStatus(200);
        $response->assertSee($firstCustomer->firstname);
        $response->assertSee($firstCustomer->lastname);
    }

    public function test_admin_customer_get_with_invalid_permission(): void
    {
        $id = Customer::create([
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'admin@admin.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0323456789',
            'password' => 'password',
        ])->id;
        $response = $this->performAdminAction('GET', self::API_URL.'/'.$id, ['admin.manage_products']);
        $response->assertStatus(200);
    }

    public function test_admin_customer_get(): void
    {
        $id = Customer::create([
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'admin@admin.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0323456789',
            'password' => 'password',
        ])->id;
        $response = $this->performAdminAction('GET', self::API_URL.'/'.$id, []);
        $response->assertStatus(200);
    }

    public function test_admin_customer_update(): void
    {
        $customer = Customer::create([
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'admin@admin.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0323456789',
            'password' => 'password',
        ]);
        $response = $this->performAdminAction('PUT', self::API_URL.'/'.$customer->id, [
            'firstname' => 'Martin',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'city' => 'roubaix',
            'phone' => '0323456710',
            'id' => $customer->id,
            'email' => 'admin@administration.com',
        ]);
        $response->assertRedirect();
        $this->assertEquals('Martin', $customer->fresh()->firstname);
    }

    public function test_admin_customer_update_with_invalid_data()
    {

        $customer = Customer::create([
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'admin@admin.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0323456789',
            'password' => 'password',
        ]);
        $response = $this->performAdminAction('PUT', self::API_URL.'/'.$customer->id, [
            'firstname' => 'Martin',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'city' => 'roubaix',
            'phone' => 'aaaa',
            'id' => $customer->id,
            'email' => 'admin@administration.com',
        ]);
        $response->assertJsonValidationErrors('phone');
    }

    public function test_admin_customer_update_change_password()
    {
        $customer = Customer::create([
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'admin@admin.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0323456789',
            'password' => 'password',
        ]);
        $response = $this->performAdminAction('PUT', self::API_URL.'/'.$customer->id, [
            'firstname' => 'Martin',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'city' => 'roubaix',
            'phone' => '0323456710',
            'id' => $customer->id,
            'email' => 'admin@administration.com',
            'password' => 'newpassword',
        ]);
        $response->assertRedirect();
        $this->assertEquals('Martin', $customer->fresh()->firstname);
        $this->assertTrue(Hash::check('newpassword', $customer->fresh()->password));
    }

    public function test_admin_customer_update_with_invalid_permission()
    {
        $id = Customer::create([
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'admin@admin.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0323456789',
            'password' => 'password',
        ])->id;
        $response = $this->performAdminAction('PUT', self::API_URL.'/'.$id, [
            'firstname' => 'Martin',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'city' => 'roubaix',
            'phone' => '0323456710',
            'id' => $id,
            'email' => 'admin@administration.com',
        ], ['admin.show_customers']);
        $response->assertStatus(403);
    }

    public function test_admin_customer_delete()
    {
        $id = Customer::create([
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'admin@admin.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0323456789',
            'password' => 'password',
        ])->id;
        $response = $this->performAdminAction('delete', self::API_URL.'/'.$id, [
            'firstname' => 'Martin',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'city' => 'roubaix',
            'phone' => '0323456710',
            'id' => $id,
            'email' => 'admin@administration.com',
        ]);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    public function test_admin_customer_confirm_customer()
    {
        $customer = Customer::create([
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'admin@admin.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0323456789',
            'password' => 'password',
        ]);
        $id = $customer->id;

        $response = $this->performAdminAction('get', self::API_URL.'/'.$id.'/confirm');
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $this->assertEquals(1, $customer->fresh()->is_confirmed);
    }

    public function test_admin_customer_confirm_customer_already_confirmed()
    {
        $customer = Customer::create([
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'admin@admin.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0323456789',
            'password' => 'password',
        ]);
        $customer->markEmailAsVerified();
        $id = $customer->id;

        $response = $this->performAdminAction('get', self::API_URL.'/'.$id.'/confirm');
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    public function test_admin_customer_send_forgot_password()
    {
        $this->seed(EmailTemplateSeeder::class);
        $customer = Customer::create([
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'admin@admin.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0323456789',
            'password' => 'password',
        ]);
        $id = $customer->id;

        $response = $this->performAdminAction('get', self::API_URL.'/'.$id.'/send_password');
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('email_messages', 1);
    }

    public function test_admin_customer_autologin()
    {

        $customer = Customer::create([
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'admin@admin.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0323456789',
            'password' => 'password',
        ]);
        $id = $customer->id;
        $response = $this->performAdminAction('get', self::API_URL.'/'.$id.'/autologin');
        $response->assertSessionHas('autologin');
        $response->assertSessionHas('autologin_customer');
        $this->assertEquals($customer->id, session('autologin_customer'));
    }

    public function test_admin_customer_suspend_customer()
    {
        $customer = Customer::create([
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'admin@admin.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0323456789',
            'password' => 'password',
        ]);
        $id = $customer->id;
        $response = $this->performAdminAction('post', self::API_URL.'/'.$id.'/action/suspend');
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $this->assertEquals(1, $customer->fresh()->isSuspended());
    }

    public function test_admin_customer_reactivate_customer()
    {
        $customer = Customer::create([
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'admin@admin.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0323456789',
            'password' => 'password',
        ]);
        $customer->suspend('Test reason', false, false);
        $id = $customer->id;
        $response = $this->performAdminAction('post', self::API_URL.'/'.$id.'/action/reactivate');
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $this->assertEquals(0, $customer->fresh()->isSuspended());
    }

    public function test_admin_customer_ban_customer()
    {

        $customer = Customer::create([
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'admin@admin.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0323456789',
            'password' => 'password',
        ]);
        $customer->ban('Test reason', false, false);
        $id = $customer->id;
        $response = $this->performAdminAction('post', self::API_URL.'/'.$id.'/action/ban');
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $this->assertEquals(1, $customer->fresh()->isBanned());
    }

    public function test_admin_customer_disabled_2fa_customer()
    {
        /** @var Customer $customer */
        $customer = Customer::create([
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'admin@admin.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0323456789',
            'password' => 'password',
        ]);
        $customer->twoFactorEnable('Test secret');
        $id = $customer->id;
        $response = $this->performAdminAction('post', self::API_URL.'/'.$id.'/action/disable2FA');
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $this->assertEquals(0, $customer->fresh()->twoFactorEnabled());
    }
}

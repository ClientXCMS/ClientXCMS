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
namespace Tests\Feature\Auth;

use App\Models\Account\Customer;
use App\Models\Admin\Setting;
use App\Services\SettingsService;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\RefreshExtensionDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;
    use RefreshExtensionDatabase;

    public function test_new_users_can_register(): void
    {
        $this->seed(EmailTemplateSeeder::class);
        $response = $this->post('/register', [
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'test@example.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0123456789',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/client/onboarding');
    }

    public function test_show_register_form(): void
    {
        $this->migrateExtension('socialauth');
        $response = $this->get('/register');
        $response->assertOk();
    }

    public function test_new_users_cannot_register_because_zipcode(): void
    {
        $response = $this->post('/register', [
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => 'Test User',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'test@example.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0123456789',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $response->assertSessionHasErrors(['zipcode']);
        $this->assertGuest();
    }

    public function test_new_users_can_register_because_tos_accepted(): void
    {
        $this->seed(EmailTemplateSeeder::class);

        app(SettingsService::class)->set('register_toslink', 'https://example.com/tos');
        $response = $this->post('/register', [
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'test@example.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0123456789',
            'accept_tos' => 'on',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $this->assertAuthenticated();
    }

    public function test_new_users_cannot_register_because_tos_not_accepted(): void
    {
        Setting::updateSettings(['register_toslink' => 'https://example.com/tos']);
        $response = $this->post('/register', [
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'test@example.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0123456789',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $response->assertSessionHasErrors(['accept_tos']);
        $this->assertGuest();
    }

    public function test_new_users_can_register_after_deleted_account()
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
        $customer->delete();
        $response = $this->post('/register', [
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'admin@admin.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0123456789',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $this->assertAuthenticated();
    }

    public function test_register_with_banned_email()
    {
        Setting::updateSettings(['banned_emails' => 'banned@clientxcms.com']);
        $response = $this->post('/register', [
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'banned@clientxcms.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0123456789',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $response->assertSessionHas('error');
    }

    public function test_register_with_banned_email_and_banned_domains()
    {

        Setting::updateSettings(['banned_emails' => 'banned@clientxcms.com,clientxcms.com']);
        $response = $this->post('/register', [
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'banned@clientxcms.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0123456789',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $response->assertSessionHas('error');
    }

    public function test_register_with_auto_confirmation()
    {
        Setting::updateSettings(['auto_confirm_registration' => 'true']);

        $response = $this->post('/register', [
            'firstname' => 'Test User',
            'lastname' => 'Test User',
            'zipcode' => '59100',
            'region' => 'Test User',
            'country' => 'FR',
            'email' => 'test@example.com',
            'address' => 'test',
            'city' => 'test',
            'phone' => '0123456789',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $this->assertAuthenticated();
        $response->assertRedirect('/client');
    }
}

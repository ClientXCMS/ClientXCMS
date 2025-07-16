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
use App\Models\Admin\Admin;
use App\Models\Admin\Setting;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\RefreshExtensionDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;
    use RefreshExtensionDatabase;

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = Customer::factory()->create();
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $this->assertAuthenticated();
        $response->assertRedirect();
    }

    public function test_show_login_form(): void
    {
        $this->migrateExtension('socialauth');
        $response = $this->get('/login');
        $response->assertOk();
    }

    public function test_show_forgot_form(): void
    {
        $response = $this->get('/forgot-password');
        $response->assertOk();
    }

    public function test_can_reset_password(): void
    {
        $user = Customer::factory()->create();
        $data = [
            'email' => $user->email,
        ];
        $response = $this->post('/forgot-password', $data);
        $response->assertFound();
        $response->assertSessionHas('success');

    }

    public function test_cannot_reset_because_reset_password_disabled(): void
    {
        Setting::updateSettings('allow_reset_password', false);
        $user = Customer::factory()->create();
        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);
        $response->assertFound();
        $response->assertSessionHas('error');
    }

    public function test_cannot_reset_password_with_invalid_email()
    {
        $user = Customer::factory()->create();
        $response = $this->post('/forgot-password', [
            'email' => 'fake@clientxcms.com',
        ]);
        $response->assertSessionHas('success');
    }

    public function test_show_reset_password_form(): void
    {
        $user = Customer::factory()->create();
        $token = Password::createToken($user);
        $response = $this->get('/reset-password/'.$token);
        $response->assertOk();
    }

    public function test_can_reset_password_with_valid_token(): void
    {
        $user = Customer::factory()->create();
        $token = Password::createToken($user);
        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);
        $response->assertRedirect();
    }

    public function test_cannot_reset_password_with_invalid_token(): void
    {
        $user = Customer::factory()->create();
        $response = $this->post('/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);
        $response->assertSessionHasErrors(['email']);
    }

    public function test_can_reset_password_with_valid_token_and_login_after_reset(): void
    {
        $user = Customer::factory()->create();
        $token = Password::createToken($user);
        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);
        $response->assertRedirect();
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'new-password',
        ]);
        $this->assertAuthenticated();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = Customer::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = Customer::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect();
    }

    public function test_users_can_resend_verify_email(): void
    {
        $user = Customer::create([
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'country' => fake()->countryCode(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->streetAddress(),
            'address2' => fake()->secondaryAddress(),
            'city' => fake()->city(),
            'region' => fake()->state(),
            'zipcode' => fake()->postcode(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
        ]);
        $this->seed(EmailTemplateSeeder::class);

        $response = $this->actingAs($user)->get('/email/verification-notification');
        $this->assertAuthenticated();
        $response->assertRedirect();
    }

    public function test_users_cannot_resend_verify_email(): void
    {
        $user = Customer::factory()->create();
        $user->markEmailAsVerified();
        $response = $this->actingAs($user)->get('/email/verification-notification');
        $this->assertAuthenticated();
        $response->assertRedirect();
    }

    public function test_users_cannot_login_because_customer_is_banned()
    {
        $user = Customer::factory()->create();
        $admin = Admin::factory()->create();
        $user->ban('Tests', false, false, $admin);
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);
        $error = session('errors')->get('email')[0];
        $this->assertStringContainsString('Tests', $error);
    }

    public function test_users_can_login_because_customer_is_suspend()
    {
        $user = Customer::factory()->create();
        $admin = Admin::factory()->create();
        $user->suspend('Tests', false, false, $admin);
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $this->assertAuthenticated();
        $response->assertRedirect();
    }

    public function test_users_can_access_supportpage_event_if_suspended()
    {
        $user = Customer::factory()->create();
        $admin = Admin::factory()->create();
        $user->suspend('Tests', false, false, $admin);
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $response->assertRedirect();
        $response = $this->actingAs($user)->get('/client/support');
        $response->assertOk();
        $response->assertSessionHas('warning');
    }

    public function test_users_cannot_access_to_not_allowed_page_if_suspended()
    {
        $user = Customer::factory()->create();
        $admin = Admin::factory()->create();
        $user->suspend('Tests', false, false, $admin);
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $response = $this->actingAs($user)->get('/client/services');
        $response->assertRedirect();
        $response->assertSessionHas('warning');
    }
}

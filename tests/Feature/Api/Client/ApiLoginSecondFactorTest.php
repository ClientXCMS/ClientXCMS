<?php

namespace Tests\Feature\Api\Client;

use App\Mail\Auth\TwoFactorCodeEmail;
use App\Models\Account\Customer;
use App\Models\Admin\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PragmaRX\Google2FA\Google2FA;
use Tests\RefreshExtensionDatabase;
use Tests\TestCase;

class ApiLoginSecondFactorTest extends TestCase
{
    use RefreshDatabase;
    use RefreshExtensionDatabase;

    private const PASSWORD = 'password123456';

    private const TOTP_SECRET = 'JBSWY3DPEHPK3PXP';

    public function test_forced_two_factor_without_totp_requires_email_code(): void
    {
        Notification::fake();
        Setting::updateSettings(['force_2fa_client' => 'true']);
        $customer = $this->customer();

        $response = $this->login($customer);

        $this->assertPendingOnly($customer, $response, 'email');
        Notification::assertSentTo($customer, TwoFactorCodeEmail::class);
    }

    public function test_email_on_new_ip_requires_email_code(): void
    {
        Notification::fake();
        $customer = $this->customer();
        $customer->setTwoFactorEmailOnNewIp(true);

        $response = $this->login($customer);

        $this->assertPendingOnly($customer, $response, 'email');
        Notification::assertSentTo($customer, TwoFactorCodeEmail::class);
    }

    public function test_totp_only_login_asks_for_totp_without_sending_email(): void
    {
        Notification::fake();
        $customer = $this->customer();
        $customer->twoFactorEnable(self::TOTP_SECRET);

        $response = $this->login($customer);

        $this->assertPendingOnly($customer, $response, 'totp');
        Notification::assertNothingSent();

        $this->verify($response->json('token'), $this->totp())
            ->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'customer']);
        $this->assertContains('client-api', $this->abilities($customer));
    }

    public function test_totp_and_email_login_asks_totp_first_without_sending_email(): void
    {
        Notification::fake();
        $customer = $this->customer();
        $customer->twoFactorEnable(self::TOTP_SECRET);
        $customer->setTwoFactorEmailOnNewIp(true);

        $response = $this->login($customer);

        $this->assertPendingOnly($customer, $response, 'totp_email');
        Notification::assertNothingSent();
    }

    public function test_email_code_alone_delivers_full_access_token(): void
    {
        Notification::fake();
        Setting::updateSettings(['force_2fa_client' => 'true']);
        $customer = $this->customer();
        $pending = $this->login($customer)->json('token');
        $this->seedEmailCode($customer, '424242');

        $response = $this->verify($pending, '424242');

        $response->assertOk()->assertJsonStructure(['token', 'token_type', 'customer']);
        $this->assertSame(['client-api'], $this->abilities($customer));
    }

    public function test_wrong_email_code_is_rejected(): void
    {
        Notification::fake();
        Setting::updateSettings(['force_2fa_client' => 'true']);
        $customer = $this->customer();
        $pending = $this->login($customer)->json('token');
        $this->seedEmailCode($customer, '424242');

        $this->verify($pending, '111111')->assertUnprocessable()->assertJsonValidationErrors(['code']);
        $this->assertNotContains('client-api', $this->abilities($customer));
    }

    public function test_totp_then_email_walk_through(): void
    {
        Notification::fake();
        $customer = $this->customer();
        $customer->twoFactorEnable(self::TOTP_SECRET);
        $customer->setTwoFactorEmailOnNewIp(true);
        $pending = $this->login($customer)->json('token');

        $step1 = $this->verify($pending, $this->totp());

        $step1->assertOk()->assertJson(['requires_2fa' => true, 'second_factor' => 'email']);
        $this->assertNotContains('client-api', $this->abilities($customer));
        Notification::assertSentTo($customer, TwoFactorCodeEmail::class);

        $this->seedEmailCode($customer, '424242');
        $this->app['auth']->forgetGuards();
        $this->verify($step1->json('token'), '424242')->assertOk()->assertJsonStructure(['token', 'customer']);
        $this->assertSame(['client-api'], $this->abilities($customer));
    }

    public function test_email_code_cannot_replace_totp_when_both_are_required(): void
    {
        Notification::fake();
        $customer = $this->customer();
        $customer->twoFactorEnable(self::TOTP_SECRET);
        $customer->setTwoFactorEmailOnNewIp(true);
        $pending = $this->login($customer)->json('token');
        $this->seedEmailCode($customer, '424242');

        $this->verify($pending, '424242')->assertUnprocessable();
        $this->assertNotContains('client-api', $this->abilities($customer));
    }

    public function test_trusted_ip_skips_second_factor(): void
    {
        Notification::fake();
        $customer = $this->customer();
        $customer->twoFactorEnable(self::TOTP_SECRET);
        $customer->setTwoFactorEmailOnNewIp(true);
        $customer->trustTwoFactorIp('127.0.0.1');

        $response = $this->login($customer->fresh());

        $response->assertOk()->assertJson(['requires_2fa' => false]);
        $this->assertSame(['client-api'], $this->abilities($customer));
        Notification::assertNothingSent();
    }

    public function test_login_without_second_factor_delivers_full_access_token(): void
    {
        $customer = $this->customer();

        $this->login($customer)->assertOk()->assertJson(['requires_2fa' => false]);
        $this->assertSame(['client-api'], $this->abilities($customer));
    }

    public function test_half_verified_token_is_refused_on_business_routes(): void
    {
        $customer = $this->customer();
        $token = $customer->createToken('2fa-pending', ['2fa:pending', '2fa:totp-done'])->plainTextToken;

        $this->withHeaders(['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'])
            ->getJson('/api/client/profile')
            ->assertForbidden();
    }

    public function test_email_resend_is_refused_to_full_access_token(): void
    {
        $customer = $this->customer();
        $token = $customer->createToken('client-api', ['client-api'])->plainTextToken;

        $this->withHeaders(['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'])
            ->postJson('/api/client/auth/2fa/email')
            ->assertForbidden();
    }

    private function customer(): Customer
    {
        return Customer::factory()->create(['password' => Hash::make(self::PASSWORD)]);
    }

    private function login(Customer $customer)
    {
        return $this->postJson('/api/client/auth/login', ['email' => $customer->email, 'password' => self::PASSWORD]);
    }

    private function verify(string $token, string $code)
    {
        return $this->withHeaders(['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'])
            ->postJson('/api/client/auth/2fa/verify', ['code' => $code]);
    }

    private function assertPendingOnly(Customer $customer, $response, string $factor): void
    {
        $response->assertOk()->assertJson(['requires_2fa' => true, 'second_factor' => $factor, 'token_type' => 'Bearer']);
        $this->assertNotContains('client-api', $this->abilities($customer), 'full access token issued');
    }

    private function abilities(Customer $customer): array
    {
        return $customer->tokens()->get()->pluck('abilities')->flatten()->unique()->values()->all();
    }

    private function seedEmailCode(Customer $customer, string $code): void
    {
        $customer->attachMetadata('2fa_email_code', Hash::make($code));
        $customer->attachMetadata('2fa_email_code_expires_at', now()->addMinutes(5)->toDateTimeString());
    }

    private function totp(): string
    {
        return (new Google2FA)->getCurrentOtp(self::TOTP_SECRET);
    }
}

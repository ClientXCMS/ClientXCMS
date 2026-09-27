<?php

namespace Tests\Feature\Api\Client;

use App\Models\Account\Customer;
use App\Models\Admin\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PragmaRX\Google2FA\Google2FA;
use Tests\RefreshExtensionDatabase;
use Tests\TestCase;

class ApiSecondFactorThrottleTest extends TestCase
{
    use RefreshDatabase;
    use RefreshExtensionDatabase;

    public function test_api_second_factor_verification_is_throttled(): void
    {
        $customer = Customer::factory()->create();
        $secret = (new Google2FA)->generateSecretKey();
        $customer->twoFactorEnable($secret);
        $current = (new Google2FA)->getCurrentOtp($secret);
        $wrong = $current === '000000' ? '111111' : '000000';
        $headers = $this->pendingHeaders($customer);

        $statuses = [];
        for ($i = 1; $i <= 7; $i++) {
            $statuses[] = $this->withHeaders($headers)->postJson('/api/client/auth/2fa/verify', ['code' => $wrong])->status();
        }

        $this->assertSame(422, $statuses[5], 'sixth attempt should still be a plain rejection: '.json_encode($statuses));
        $this->assertSame(429, $statuses[6], 'statuses: '.json_encode($statuses));
    }

    public function test_email_code_resend_is_throttled(): void
    {
        Notification::fake();
        Setting::updateSettings(['force_2fa_client' => 'true']);
        $customer = Customer::factory()->create();
        $headers = $this->pendingHeaders($customer);

        $statuses = [];
        for ($i = 1; $i <= 4; $i++) {
            $statuses[] = $this->withHeaders($headers)->postJson('/api/client/auth/2fa/email')->status();
        }

        $this->assertSame([200, 200, 200, 429], $statuses);
    }

    public function test_email_resend_is_refused_when_no_email_code_is_required(): void
    {
        Notification::fake();
        $customer = Customer::factory()->create();
        $customer->twoFactorEnable((new Google2FA)->generateSecretKey());

        $this->withHeaders($this->pendingHeaders($customer))->postJson('/api/client/auth/2fa/email')->assertStatus(409);
        Notification::assertNothingSent();
    }

    public function test_email_resend_is_refused_before_totp_is_validated(): void
    {
        Notification::fake();
        $customer = Customer::factory()->create();
        $customer->twoFactorEnable((new Google2FA)->generateSecretKey());
        $customer->setTwoFactorEmailOnNewIp(true);

        $this->withHeaders($this->pendingHeaders($customer))->postJson('/api/client/auth/2fa/email')->assertStatus(409);
        Notification::assertNothingSent();
    }

    public function test_email_resend_during_cooldown_is_refused(): void
    {
        Notification::fake();
        Setting::updateSettings(['force_2fa_client' => 'true']);
        $customer = Customer::factory()->create();
        $customer->attachMetadata('2fa_email_burned_cycles', '3');
        $customer->attachMetadata('2fa_email_burned_at', now()->toDateTimeString());

        $this->withHeaders($this->pendingHeaders($customer))->postJson('/api/client/auth/2fa/email')->assertStatus(429);
        Notification::assertNothingSent();
    }

    public function test_web_session_cannot_use_second_factor_routes(): void
    {
        Notification::fake();
        Setting::updateSettings(['force_2fa_client' => 'true']);
        $customer = Customer::factory()->create();
        $customer->twoFactorEnable((new Google2FA)->generateSecretKey());
        $customer->setTwoFactorEmailOnNewIp(true);

        $this->actingAs($customer, 'web')->postJson('/api/client/auth/2fa/verify', ['code' => '123456'])
            ->assertUnauthorized()->assertJson(['error' => __('auth.unauthenticated')]);
        $this->actingAs($customer, 'web')->postJson('/api/client/auth/2fa/email')
            ->assertUnauthorized()->assertJson(['error' => __('auth.unauthenticated')]);
        $this->assertSame(0, $customer->tokens()->count());
        Notification::assertNothingSent();
    }

    private function pendingHeaders(Customer $customer): array
    {
        $token = $customer->createToken('2fa-pending', ['2fa:pending'], now()->addMinutes(5))->plainTextToken;

        return ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
    }
}

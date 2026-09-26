<?php

namespace Tests\Feature\Api\Client;

use App\Models\Account\Customer;
use App\Models\Admin\Admin;
use App\Models\Billing\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use PragmaRX\Google2FA\Google2FA;
use Tests\RefreshExtensionDatabase;
use Tests\TestCase;

class ClientApiAuthorizationTest extends TestCase
{
    use RefreshDatabase;
    use RefreshExtensionDatabase;

    private const TOTP_SECRET = 'JBSWY3DPEHPK3PXP';

    private function statefulHeaders(): array
    {
        return [
            'Referer' => config('app.url'),
            'Origin' => config('app.url'),
            'Accept' => 'application/json',
        ];
    }

    private function bearer(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
    }

    public function test_session_with_pending_second_factor_is_refused(): void
    {
        $customer = Customer::factory()->create();
        $customer->twoFactorEnable(self::TOTP_SECRET);
        Session::forget('2fa_verified');
        $this->assertFalse($customer->twoFactorVerified());

        $response = $this->actingAs($customer, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/client/profile/2fa/recovery-codes');

        $response->assertForbidden();
        $response->assertJsonMissingPath('recovery_codes');
    }

    public function test_real_login_then_pending_second_factor_then_api_call_is_refused(): void
    {
        $customer = Customer::factory()->create();
        $customer->twoFactorEnable(self::TOTP_SECRET);
        Session::forget('2fa_verified');

        $this->post('/login', ['email' => $customer->email, 'password' => 'password']);
        $this->assertAuthenticatedAs($customer, 'web');
        $this->assertFalse($customer->twoFactorVerified());

        $response = $this->withHeaders(['Referer' => config('app.url'), 'Accept' => 'application/json'])
            ->getJson('/api/client/profile/2fa/recovery-codes');

        $response->assertForbidden();
        $response->assertJsonMissingPath('recovery_codes');
    }

    public function test_customer_session_with_validated_second_factor_is_refused(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($customer, 'web')
            ->withSession(['2fa_verified' => true])
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/client/profile');

        $response->assertForbidden();
    }

    public function test_admin_token_sharing_customer_id_is_refused(): void
    {
        $admin = Admin::factory()->create();
        $customer = Customer::factory()->make();
        $customer->forceFill(['id' => $admin->id])->save();
        $this->assertSame($admin->id, $customer->id);
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id, 'status' => Invoice::STATUS_PENDING]);

        $token = $admin->createToken('admin-api', ['*'])->plainTextToken;
        $response = $this->withHeaders($this->bearer($token))->getJson('/api/client/invoices');

        $response->assertForbidden();
        $this->assertStringNotContainsString($invoice->uuid, $response->getContent());
    }

    public function test_admin_session_is_refused(): void
    {
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/client/profile');

        $response->assertForbidden();
    }

    public function test_banned_customer_token_is_refused(): void
    {
        $customer = Customer::factory()->create();
        $token = $customer->createToken('client-api', ['client-api'])->plainTextToken;
        $customer->ban('reason', false, false, $this->createAdminModel());
        $this->assertTrue($customer->fresh()->isBanned());

        $this->withHeaders($this->bearer($token))->getJson('/api/client/profile')->assertForbidden();
        $this->withHeaders($this->bearer($token))->getJson('/api/client/tickets')->assertForbidden();
    }

    private function suspendedCustomerToken(array $abilities = ['client-api']): array
    {
        $customer = Customer::factory()->create();
        $token = $customer->createToken('client-api', $abilities)->plainTextToken;
        $customer->suspend('reason', false, false, $this->createAdminModel());
        $this->assertTrue($customer->fresh()->isSuspended());

        return [$customer, $token];
    }

    public function test_suspended_customer_token_is_refused_outside_support(): void
    {
        [, $token] = $this->suspendedCustomerToken();

        $this->withHeaders($this->bearer($token))->putJson('/api/client/profile', ['firstname' => 'Jane'])->assertForbidden();
        $this->withHeaders($this->bearer($token))->getJson('/api/client/invoices')->assertForbidden();
    }

    public function test_suspended_customer_token_keeps_support_access(): void
    {
        [, $token] = $this->suspendedCustomerToken();

        $this->withHeaders($this->bearer($token))->getJson('/api/client/tickets')->assertOk();
    }

    public function test_suspended_customer_token_keeps_profile_view(): void
    {
        [, $token] = $this->suspendedCustomerToken();

        $this->withHeaders($this->bearer($token))->getJson('/api/client/profile')->assertOk();
    }

    public function test_suspended_customer_can_log_out(): void
    {
        [$customer, $token] = $this->suspendedCustomerToken();

        $this->withHeaders($this->bearer($token))->postJson('/api/client/auth/logout')->assertOk();
        $this->assertSame(0, $customer->tokens()->count());
    }

    public function test_suspended_customer_pending_second_factor_token_can_verify(): void
    {
        $customer = Customer::factory()->create();
        $customer->twoFactorEnable(self::TOTP_SECRET);
        $customer->suspend('reason', false, false, $this->createAdminModel());
        $pending = $customer->createToken('2fa-pending', ['2fa:pending'])->plainTextToken;
        $code = (new Google2FA)->getCurrentOtp(self::TOTP_SECRET);

        $response = $this->withHeaders($this->bearer($pending))
            ->postJson('/api/client/auth/2fa/verify', ['code' => $code]);

        $response->assertOk();
        $response->assertJsonStructure(['token', 'token_type']);
    }

    public function test_customer_token_keeps_access(): void
    {
        $customer = Customer::factory()->create();
        $token = $customer->createToken('client-api', ['client-api'])->plainTextToken;

        $this->withHeaders($this->bearer($token))->getJson('/api/client/profile')->assertOk();
    }

    public function test_pending_second_factor_token_can_still_verify(): void
    {
        $customer = Customer::factory()->create();
        $customer->twoFactorEnable(self::TOTP_SECRET);
        $pending = $customer->createToken('2fa-pending', ['2fa:pending'])->plainTextToken;
        $code = (new Google2FA)->getCurrentOtp(self::TOTP_SECRET);

        $response = $this->withHeaders($this->bearer($pending))
            ->postJson('/api/client/auth/2fa/verify', ['code' => $code]);

        $response->assertOk();
        $response->assertJsonStructure(['token', 'token_type']);
    }
}

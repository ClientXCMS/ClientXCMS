<?php

namespace Tests\Feature\Auth;

use App\Models\Account\Customer;
use App\Models\Admin\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Tests\TestCase;

class PasskeyInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_passkey_endpoints_are_hidden_when_feature_is_disabled(): void
    {
        $this->getJson(route('passkey.login-options'))->assertNotFound();
    }

    public function test_enabled_login_options_are_anonymous_and_require_user_verification(): void
    {
        Setting::updateSettings('passkeys_enabled', 'true');

        $this->getJson(route('passkey.login-options'))
            ->assertOk()
            ->assertJsonPath('options.userVerification', 'required')
            ->assertJsonPath('options.allowCredentials', []);
    }

    public function test_customer_is_a_passkey_user_with_an_opaque_handle(): void
    {
        $customer = Customer::factory()->create();

        $this->assertInstanceOf(PasskeyUser::class, $customer);
        $this->assertSame(32, strlen($customer->getPasskeyUserHandle()));
        $this->assertSame($customer->email, $customer->getPasskeyUsername());
    }

    public function test_password_confirmation_allows_passkey_management(): void
    {
        Setting::updateSettings('passkeys_enabled', 'true');
        $customer = Customer::factory()->create();

        $this->actingAs($customer, 'web')
            ->post(route('password.confirm.store'), ['password' => 'password'])
            ->assertRedirect(route('front.profile.index'));

        $this->assertIsInt(session('auth.password_confirmed_at'));
    }

    public function test_customer_cannot_delete_another_customers_passkey(): void
    {
        Setting::updateSettings('passkeys_enabled', 'true');
        $owner = Customer::factory()->create();
        $attacker = Customer::factory()->create();
        $passkey = $owner->passkeys()->create([
            'name' => 'Owner device',
            'credential_id' => 'unique-credential',
            'credential' => [],
        ]);

        $this->actingAs($attacker, 'web')
            ->withSession(['auth.password_confirmed_at' => time()])
            ->deleteJson(route('front.profile.passkeys.destroy', $passkey))
            ->assertForbidden();

        $this->assertDatabaseHas('passkeys', ['id' => $passkey->id]);
    }
}

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
namespace Tests\Feature\Client;

use App\Models\Account\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\RefreshExtensionDatabase;

class ProfileControllerTest extends \Tests\TestCase
{
    use RefreshDatabase;
    use RefreshExtensionDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = Customer::factory()->create([
            'password' => \Hash::make('password123'),
        ]);
        $this->actingAs($this->user, 'web');
        $this->migrateExtension('socialauth');
    }

    public function test_profil_show()
    {
        $response = $this->get(route('front.profile.index'));
        $response->assertStatus(200);
        $response->assertViewIs('front.profile.edit');
    }

    public function test_update_profile()
    {
        $response = $this->post(route('front.profile.update'), [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'country' => 'FR',
        ]);

        $response->assertRedirect(route('front.profile.index'));
        $this->assertDatabaseHas('customers', [
            'id' => $this->user->id,
            'firstname' => 'John',
            'lastname' => 'Doe',
            'country' => 'FR',
        ]);
    }

    private function disable2FA()
    {
        $this->user->twoFactorDisable();
    }

    public function test_update_password_without_confirmation()
    {
        $response = $this->post(route('front.profile.password'), [
            'password' => 'newpassword456',
            'currentpassword' => 'wrongconfirmation',
        ]);
        $response->assertSessionHasErrors(['currentpassword']);
        $this->assertFalse(\Hash::check('newpassword456', $this->user->fresh()->password));
    }

    public function test_update_password_with_invalid_two_factor_authentification()
    {
        $this->user->attachMetadata('2fa_verified', true);
        $this->user->attachMetadata('2fa_secret', 'SOMESECRET2FAKEY');
        // pour bypass le middleware
        \Session::put('2fa_verified', true);
        $response = $this->post(route('front.profile.password'), [
            'password' => 'newpassword456',
            'confirm_password' => 'newpassword456',
            'currentpassword' => 'password123',
            '2fa' => 'test',
        ]);
        $response->assertSessionHasErrors(['2fa']);
        $this->assertFalse(\Hash::check('newpassword456', $this->user->fresh()->password));
    }

    public function test_update_password_with_valid_two_factor_authentification()
    {
        $this->user->attachMetadata('2fa_verified', true);
        $this->user->attachMetadata('2fa_secret', 'SOMESECRET2FAKEY');
        // pour bypass le middleware
        \Session::put('2fa_verified', true);

        $response = $this->post(route('front.profile.password'), [
            'password' => 'newpassword456',
            'password_confirmation' => 'newpassword456',
            'currentpassword' => 'password123',
            '2fa' => app(\PragmaRX\Google2FA\Google2FA::class)->getCurrentOtp('SOMESECRET2FAKEY'),
        ]);
        $response->assertRedirect(route('front.profile.index'));
        $this->assertTrue(\Hash::check('newpassword456', $this->user->fresh()->password));
    }

    public function test_profile_enables_two_factor_authentication()
    {
        \Session::put('2fa_secret', 'SOMESECRET2FAKEY');

        // pour bypass le middleware
        \Session::put('2fa_verified', true);
        $response = $this->post(route('front.profile.2fa'), [
            '2fa' => app(\PragmaRX\Google2FA\Google2FA::class)->getCurrentOtp('SOMESECRET2FAKEY'),
        ]);

        $response->assertRedirect();
        $this->assertNotNull($this->user->getMetadata('2fa_secret'));
    }

    public function test_profile_disables_two_factor_authentication()
    {
        $this->user->attachMetadata('2fa_verified', true);
        $this->user->attachMetadata('2fa_secret', 'SOMESECRET2FAKEY');
        // pour bypass le middleware
        \Session::put('2fa_verified', true);
        $response = $this->post(route('front.profile.2fa'), [
            '2fa' => app(\PragmaRX\Google2FA\Google2FA::class)->getCurrentOtp('SOMESECRET2FAKEY'),
        ]);

        $response->assertRedirect();
        $this->assertNull($this->user->getMetadata('2fa_secret'));
    }

    public function test_downloads_recovery_codes()
    {
        $this->user->attachMetadata('2fa_verified', true);
        $codes = $this->user->twoFactorRecoveryCodes();

        $response = $this->get(route('front.profile.2fa_codes'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename=2fa_recovery_codes_'.\Str::slug(config('app.name')).'.txt');
        $this->assertContains($codes[0], $this->user->twoFactorRecoveryCodes());
    }
}

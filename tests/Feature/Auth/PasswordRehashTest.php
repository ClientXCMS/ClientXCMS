<?php

namespace Tests\Feature\Auth;

use App\Core\Auth\MigratingHashManager;
use App\Models\Account\Customer;
use App\Models\Admin\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordRehashTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct horse battery staple';

    private function bcryptHash(string $plain): string
    {
        return (new BcryptHasher(['rounds' => 4]))->make($plain);
    }

    private function useArgon2id(): void
    {
        if (! in_array('argon2id', MigratingHashManager::availableDrivers(), true)) {
            $this->markTestSkipped('this PHP build has no argon2id support');
        }
        config()->set('hashing.driver', 'argon2id');
    }

    public function test_an_admin_hashed_with_bcrypt_still_logs_in_after_switching_to_argon2id(): void
    {
        $admin = Admin::factory()->create(['password' => $this->bcryptHash(self::PASSWORD)]);
        $this->useArgon2id();

        $response = $this->post(route('admin.login'), [
            'email' => $admin->email,
            'password' => self::PASSWORD,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($admin->fresh(), 'admin');
    }

    public function test_the_admin_hash_is_migrated_to_the_configured_driver_on_login(): void
    {
        $admin = Admin::factory()->create(['password' => $this->bcryptHash(self::PASSWORD)]);
        $this->useArgon2id();

        $this->post(route('admin.login'), ['email' => $admin->email, 'password' => self::PASSWORD]);

        $this->assertSame('argon2id', password_get_info($admin->fresh()->password)['algoName']);
    }

    public function test_an_admin_can_log_in_again_once_the_hash_has_been_migrated(): void
    {
        $admin = Admin::factory()->create(['password' => $this->bcryptHash(self::PASSWORD)]);
        $this->useArgon2id();

        $this->post(route('admin.login'), ['email' => $admin->email, 'password' => self::PASSWORD]);
        auth('admin')->logout();
        $response = $this->post(route('admin.login'), [
            'email' => $admin->email,
            'password' => self::PASSWORD,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($admin->fresh(), 'admin');
    }

    public function test_a_customer_hashed_with_bcrypt_still_logs_in_after_switching_to_argon2id(): void
    {
        $customer = Customer::factory()->create(['password' => $this->bcryptHash(self::PASSWORD)]);
        $this->useArgon2id();

        $response = $this->post(route('login'), [
            'email' => $customer->email,
            'password' => self::PASSWORD,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($customer->fresh(), 'web');
        $this->assertSame('argon2id', password_get_info($customer->fresh()->password)['algoName']);
    }

    public function test_a_wrong_password_is_still_refused_across_algorithms(): void
    {
        $admin = Admin::factory()->create(['password' => $this->bcryptHash(self::PASSWORD)]);
        $this->useArgon2id();

        $response = $this->post(route('admin.login'), [
            'email' => $admin->email,
            'password' => 'not the password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('admin');
    }

    public function test_the_hasher_verifies_each_hash_with_its_own_algorithm(): void
    {
        $this->useArgon2id();

        $this->assertTrue(Hash::check(self::PASSWORD, $this->bcryptHash(self::PASSWORD)));
        $this->assertTrue(Hash::check(self::PASSWORD, Hash::make(self::PASSWORD)));
        $this->assertFalse(Hash::check('wrong', $this->bcryptHash(self::PASSWORD)));
    }

    public function test_it_only_offers_drivers_this_php_build_can_produce(): void
    {
        $available = MigratingHashManager::availableDrivers();

        $this->assertContains('bcrypt', $available);
        foreach ($available as $driver) {
            $this->assertNotNull(Hash::driver($driver)->make(self::PASSWORD));
        }
    }
}

<?php

namespace Tests\Feature\Api\Client;

use App\Services\Auth\DummyPasswordHash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\RefreshExtensionDatabase;
use Tests\TestCase;

class ApiLoginTimingTest extends TestCase
{
    use RefreshDatabase;
    use RefreshExtensionDatabase;

    public function test_api_login_with_unknown_email_performs_password_hash_check(): void
    {
        DummyPasswordHash::get();
        Hash::spy();

        $response = $this->postJson('/api/client/auth/login', ['email' => 'nobody-'.uniqid().'@example.com', 'password' => 'whatever-password']);

        $response->assertUnprocessable()->assertJsonValidationErrors(['email' => __('auth.failed')]);
        Hash::shouldHaveReceived('check')->once();
        Hash::shouldNotHaveReceived('make');
    }

    public function test_unknown_email_is_refused_even_if_password_matches_the_dummy_hash(): void
    {
        Cache::forever('auth.dummy_password_hash', Hash::make('matches-dummy'));

        $this->postJson('/api/client/auth/login', ['email' => 'nobody-'.uniqid().'@example.com', 'password' => 'matches-dummy'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email' => __('auth.failed')]);
    }

    public function test_dummy_hash_is_computed_once_and_is_a_valid_hash(): void
    {
        $first = DummyPasswordHash::get();

        $this->assertSame($first, DummyPasswordHash::get());
        $this->assertFalse(Hash::needsRehash($first));
        $this->assertFalse(Hash::check('', $first));
    }
}

<?php

namespace Tests\Feature\Api\Client;

use App\Models\Account\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\RefreshExtensionDatabase;
use Tests\TestCase;

class ApiResetPasswordEnumerationTest extends TestCase
{
    use RefreshDatabase;
    use RefreshExtensionDatabase;

    public function test_api_reset_password_answers_identically_for_unknown_and_known_email(): void
    {
        $customer = Customer::factory()->create();
        $payload = ['token' => str_repeat('a', 64), 'password' => 'N3w-Passw0rd-Long!', 'password_confirmation' => 'N3w-Passw0rd-Long!'];

        $unknown = $this->postJson('/api/client/auth/reset-password', $payload + ['email' => 'nobody-'.uniqid().'@example.com']);
        $known = $this->postJson('/api/client/auth/reset-password', $payload + ['email' => $customer->email]);

        $known->assertUnprocessable()->assertJsonValidationErrors(['email' => __('passwords.token')]);
        $this->assertSame($known->status(), $unknown->status());
        $this->assertSame($known->json(), $unknown->json());
    }
}

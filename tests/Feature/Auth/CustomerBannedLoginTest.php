<?php

namespace Tests\Feature\Auth;

use App\Models\Account\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerBannedLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_banned_customer_cannot_login(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'banned@example.com',
            'password' => bcrypt('Password123!'),
        ]);
        $customer->attachMetadata('banned', 'true');
        $customer->attachMetadata('banned_reason', 'Test ban');

        $response = $this->post('/login', [
            'email' => 'banned@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('web', 'Banned customer must not have an active web session after a login attempt');
    }

    public function test_login_request_runs_banned_check_in_source(): void
    {
        $reflection = new \ReflectionMethod(\App\Http\Requests\Auth\LoginRequest::class, 'authenticate');
        $file = file($reflection->getFileName());
        $body = implode('', array_slice($file, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));

        $this->assertStringContainsString(
            '->isBanned()',
            $body,
            'authenticate() must check isBanned() before completing the login - otherwise a banned customer is briefly logged in (until BannedMiddleware fires on the next request)'
        );
        $this->assertStringNotContainsString('// if (auth(', $body, 'commented-out banned check must be removed');
    }
}

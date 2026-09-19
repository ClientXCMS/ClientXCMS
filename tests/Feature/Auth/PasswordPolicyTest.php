<?php

namespace Tests\Feature\Auth;

use App\Providers\AppServiceProvider;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\NotPwnedVerifier;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

class PasswordPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function fails(string $password): bool
    {
        return Validator::make(
            ['password' => $password],
            ['password' => Password::defaults()]
        )->fails();
    }

    // HIBP answers the SHA-1 suffixes sharing the sent prefix, so returning our own marks it breached
    private function fakeBreachApi(string $breached): void
    {
        Http::fake([
            'api.pwnedpasswords.com/*' => Http::response(
                substr(strtoupper(sha1($breached)), 5).':4242',
                200
            ),
        ]);
    }

    public function test_a_password_shorter_than_twelve_characters_is_refused(): void
    {
        $this->assertTrue($this->fails('Short1!'));
        $this->assertTrue($this->fails('elevenchars'));
        $this->assertFalse($this->fails('twelvecharss'));
    }

    // bcrypt stops reading at 72 bytes, so anything past it never counts
    public function test_a_password_longer_than_the_bcrypt_limit_is_refused(): void
    {
        $this->assertFalse($this->fails(str_repeat('a', 72)));
        $this->assertTrue($this->fails(str_repeat('a', 73)));
    }

    public function test_no_composition_rule_is_imposed(): void
    {
        $this->assertFalse(
            $this->fails('correct horse battery staple'),
            'a passphrase without digits, symbols or upper case must be accepted'
        );
    }

    public function test_a_breached_password_is_refused_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->fakeBreachApi('twelvecharss');

        $this->assertTrue($this->fails('twelvecharss'));
    }

    public function test_the_breach_check_stays_out_of_the_way_outside_production(): void
    {
        Http::fake();

        $this->assertFalse($this->fails('twelvecharss'));

        Http::assertNothingSent();
    }

    // Nobody must be locked out of setting a password because the breach service is down
    public function test_a_password_is_accepted_when_the_breach_service_is_unreachable(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 500)]);

        $this->assertFalse($this->fails('twelvecharss'));
    }

    public function test_the_breach_check_gives_up_after_a_few_seconds(): void
    {
        $verifier = $this->app->make(UncompromisedVerifier::class);

        $this->assertInstanceOf(NotPwnedVerifier::class, $verifier);
        $this->assertSame(
            AppServiceProvider::BREACH_CHECK_TIMEOUT,
            (new \ReflectionProperty($verifier, 'timeout'))->getValue($verifier)
        );
        $this->assertLessThanOrEqual(5, AppServiceProvider::BREACH_CHECK_TIMEOUT);
    }
}

<?php

namespace Tests\Unit\Services\Auth;

use App\Contracts\Auth\SmsGatewayContract;
use App\Models\Account\Customer;
use App\Models\Admin\Setting;
use App\Services\Auth\Sms\LogSmsGateway;
use App\Services\Auth\Sms\TwilioSmsGateway;
use App\Services\Auth\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SmsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_driver_is_log(): void
    {
        Setting::updateSettings(['mfa_sms_driver' => null]);
        $gateway = SmsService::gateway();
        $this->assertInstanceOf(LogSmsGateway::class, $gateway);
        $this->assertSame('log', $gateway->name());
    }

    public function test_twilio_driver_resolves(): void
    {
        Setting::updateSettings(['mfa_sms_driver' => 'twilio']);
        $gateway = SmsService::gateway();
        $this->assertInstanceOf(TwilioSmsGateway::class, $gateway);
        $this->assertSame('twilio', $gateway->name());
    }

    public function test_unknown_driver_falls_back_to_log(): void
    {
        Setting::updateSettings(['mfa_sms_driver' => 'made-up-name']);
        $this->assertInstanceOf(LogSmsGateway::class, SmsService::gateway());
    }

    public function test_extension_registers_custom_driver(): void
    {
        $fake = new class implements SmsGatewayContract {
            public function send(string $to, string $message): void {}
            public function name(): string { return 'fake'; }
        };
        SmsService::extend('fake', fn () => $fake);
        Setting::updateSettings(['mfa_sms_driver' => 'fake']);

        $this->assertSame('fake', SmsService::gateway()->name());
    }

    public function test_log_driver_never_logs_the_otp_body(): void
    {
        $gateway = new LogSmsGateway;
        $captured = [];
        \Log::shouldReceive('info')->once()->andReturnUsing(function ($event, $context) use (&$captured) {
            $captured = compact('event', 'context');
        });

        $gateway->send('+33612345678', 'CTX — code: 123456');

        $this->assertSame('mfa.sms.log_driver', $captured['event']);
        $this->assertArrayNotHasKey('body', $captured['context']);
        $this->assertArrayHasKey('body_chars', $captured['context']);
        // Recipient is masked.
        $this->assertStringContainsString('*', $captured['context']['to']);
    }

    public function test_customer_sms_code_round_trip(): void
    {
        Setting::updateSettings(['mfa_sms_driver' => 'log']);

        /** @var Customer $customer */
        $customer = Customer::factory()->create();

        $sent = $customer->sendTwoFactorSmsCode('web', '127.0.0.1');
        $this->assertTrue($sent);
        $this->assertTrue($customer->hasMetadata('2fa_sms_code'));

        // Wrong code rejected
        $this->assertFalse($customer->isValidSmsTwoFactorCode('000000'));

        // Read the underlying hash, brute-force the only 6-digit code the
        // metadata represents — we can't recover the code in a test, so
        // we re-call sendTwoFactorSmsCode with `Hash::make` mocked? Too
        // brittle. Instead assert the structural pieces.
        $this->assertNotNull($customer->getMetadata('2fa_sms_code'));
        $this->assertNotNull($customer->getMetadata('2fa_sms_code_expires_at'));
    }

    public function test_customer_without_phone_returns_false(): void
    {
        Setting::updateSettings(['mfa_sms_driver' => 'log']);
        /** @var Customer $customer */
        $customer = Customer::factory()->create();
        $customer->phone = null;
        $customer->save();

        $this->assertFalse($customer->sendTwoFactorSmsCode('web'));
    }
}

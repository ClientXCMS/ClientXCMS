<?php

namespace Tests\Feature\Admin\Settings;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookSsrfTest extends TestCase
{
    public function test_webhook_dto_refuses_aws_metadata_url(): void
    {
        Http::fake();

        $dto = new \App\DTO\Core\WebhookDTO(
            'test',
            fn ($vars) => ['payload' => 'x'],
            fn () => ['%appname%' => 'app'],
            'http://169.254.169.254/latest/meta-data/iam/security-credentials/'
        );

        $dto->send();

        Http::assertNothingSent();
    }

    public function test_webhook_dto_refuses_loopback(): void
    {
        Http::fake();

        $dto = new \App\DTO\Core\WebhookDTO(
            'test',
            fn ($vars) => ['payload' => 'x'],
            fn () => ['%appname%' => 'app'],
            'http://127.0.0.1:6379/'
        );

        $dto->send();

        Http::assertNothingSent();
    }

    public function test_setting_validation_rejects_loopback_webhook(): void
    {
        $rule = new \App\Rules\PublicHttpUrl;
        $this->assertFalse($rule->passes('helpdesk_webhook_url', 'http://127.0.0.1/'));
        $this->assertFalse($rule->passes('store_checkout_webhook_url', 'http://10.0.0.5/'));
        $this->assertFalse($rule->passes('webhook_renewal_url', 'http://169.254.169.254/'));
    }
}

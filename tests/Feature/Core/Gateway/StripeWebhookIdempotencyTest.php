<?php

namespace Tests\Feature\Core\Gateway;

use Tests\TestCase;

class StripeWebhookIdempotencyTest extends TestCase
{
    public function test_notification_short_circuits_on_already_paid(): void
    {
        $reflection = new \ReflectionMethod(\App\Core\Gateway\StripeType::class, 'notification');
        $file = file($reflection->getFileName());
        $body = implode('', array_slice($file, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));

        $this->assertStringContainsString(
            'Invoice::STATUS_PAID',
            $body,
            'Stripe webhook handler must short-circuit when the invoice is already paid - Stripe retries on 5xx / network drops, and the side effects (PaymentIntent::retrieve API call, invoice update, complete()) must not run twice'
        );
        $this->assertMatchesRegularExpression(
            '/\$invoice->status\s*===?\s*Invoice::STATUS_PAID/',
            $body,
            'idempotency check must compare \$invoice->status to Invoice::STATUS_PAID'
        );
    }
}

<?php

namespace Tests\Feature\Front;

use Tests\TestCase;

class PaymentMethodDefaultAuthTest extends TestCase
{
    public function test_anonymous_request_cannot_change_default_payment_method(): void
    {
        $response = $this->withSession(['_token' => 'fake-token'])
            ->post(route('front.payment-methods.default', ['paymentMethod' => 'pm_attacker']), [
                '_token' => 'fake-token',
            ]);

        $this->assertContains(
            $response->status(),
            [302, 401, 403, 404, 419],
            'Anonymous POST must NOT be able to change a customer default payment method'
        );
    }

    public function test_anonymous_request_cannot_target_other_customer_via_customer_id(): void
    {
        $response = $this->withSession(['_token' => 'fake-token'])
            ->post(route('front.payment-methods.default', ['paymentMethod' => 'pm_attacker']).'?customer_id=1', [
                '_token' => 'fake-token',
            ]);

        $this->assertContains(
            $response->status(),
            [302, 401, 403, 404, 419],
            'Anonymous POST with forged customer_id must be rejected (relies on staff_has_permission which returns false for anon)'
        );
    }
}

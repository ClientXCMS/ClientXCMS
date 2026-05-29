<?php

namespace Tests\Unit\Models\Admin;

use App\Models\Account\Customer;
use App\Models\Admin\EmailTemplate;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * v2.16 regression coverage for EmailTemplate::getMailMessage().
 *
 * The "custom" template is what backs the admin's manual mail-send flow.
 * Previously the method called MailMessage->action() unconditionally, which
 * produced an empty CTA button when the admin left the URL field blank.
 */
class EmailTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EmailTemplateSeeder::class);
    }

    public function test_get_mail_message_omits_action_when_url_is_empty(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->create();

        $mail = EmailTemplate::getMailMessage('custom', '', [], $customer);

        $this->assertNull($mail->actionText, 'No action text expected when URL is empty');
        $this->assertNull($mail->actionUrl, 'No action URL expected when URL is empty');
        $this->assertArrayNotHasKey('button_url', $mail->viewData);
        $this->assertArrayNotHasKey('button_text', $mail->viewData);
    }

    public function test_get_mail_message_keeps_action_when_url_is_provided(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->create();

        // Make sure the seeded "custom" template has a button text — fallback
        // to an explicit update so the test is independent of seeder content.
        EmailTemplate::where('name', 'custom')->update(['button_text' => 'See it']);

        $mail = EmailTemplate::getMailMessage('custom', 'https://example.com/x', [], $customer);

        $this->assertSame('See it', $mail->actionText);
        $this->assertSame('https://example.com/x', $mail->actionUrl);
        $this->assertSame('https://example.com/x', $mail->viewData['button_url']);
        $this->assertSame('See it', $mail->viewData['button_text']);
    }
}

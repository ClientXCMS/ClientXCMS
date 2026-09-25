<?php

namespace Tests\Feature\Mail;

use App\Mail\Account\CustomerAccountInvitationEmail;
use App\Mail\Auth\ResetPasswordEmail;
use App\Mail\Auth\TwoFactorCodeEmail;
use App\Mail\Auth\VerifyEmail;
use App\Models\Account\Customer;
use App\Models\Account\CustomerAccountInvitation;
use App\Models\Account\EmailMessage;
use App\Notifications\CustomMail;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SensitiveEmailsAreNotArchivedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EmailTemplateSeeder::class);
        config(['mail.default' => 'array']);
    }

    public function test_a_customer_password_reset_is_not_archived(): void
    {
        $customer = Customer::factory()->create();

        $customer->notify(new ResetPasswordEmail('plain-reset-token'));

        $this->assertArchived(0, 'A password reset link must not be readable from the mail history: it takes over the account it was sent to');
    }

    public function test_a_customer_two_factor_code_is_not_archived(): void
    {
        $customer = Customer::factory()->create();

        $customer->notify(new TwoFactorCodeEmail('123456', 'web', '203.0.113.4'));

        $this->assertArchived(0, 'A second factor sent by email must not be readable from the mail history');
    }

    public function test_an_email_verification_link_is_not_archived(): void
    {
        $customer = Customer::factory()->create();

        $customer->notify(new VerifyEmail($customer));

        $this->assertArchived(0, 'A signed verification link must not be readable from the mail history');
    }

    public function test_an_account_invitation_is_not_archived(): void
    {
        $owner = Customer::factory()->create();
        $invitation = CustomerAccountInvitation::create([
            'owner_customer_id' => $owner->id,
            'email' => 'invitee@example.com',
            'permissions' => ['service.show'],
            'all_services' => true,
        ]);

        $owner->notify(new CustomerAccountInvitationEmail($invitation, 'plain-invitation-token'));

        $this->assertArchived(0, 'The invitation token is deliberately never stored in clear: archiving the email puts it back');
    }

    public function test_an_ordinary_email_is_still_archived(): void
    {
        $customer = Customer::factory()->create();

        $customer->notify(new CustomMail([], 'Your invoice is ready.', '', '', 'Invoice ready'));

        $this->assertArchived(1, 'Emails that carry no credential keep being archived for the customer and the staff');
    }

    private function assertArchived(int $expected, string $message): void
    {
        $this->assertSame($expected, EmailMessage::count(), $message);
    }
}

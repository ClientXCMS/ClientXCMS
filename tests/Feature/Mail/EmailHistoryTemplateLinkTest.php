<?php

namespace Tests\Feature\Mail;

use App\Models\Account\Customer;
use App\Models\Account\EmailMessage;
use App\Models\Admin\EmailTemplate;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\Fixtures\Mail\PlainNotification;
use Tests\TestCase;

class EmailHistoryTemplateLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EmailTemplateSeeder::class);
        config(['mail.default' => 'array']);
    }

    public function test_deleting_a_template_keeps_the_messages_it_sent(): void
    {
        $customer = Customer::factory()->create();
        $template = EmailTemplate::where('name', 'custom')->first();
        $message = EmailMessage::factory()->create(['recipient_id' => $customer->id, 'template' => $template->id]);

        $template->delete();

        // Tidying up the templates must not wipe the mail history of every customer who received one.
        $this->assertDatabaseHas('email_messages', ['id' => $message->id, 'template' => null, 'recipient_id' => $customer->id]);
    }

    public function test_an_email_without_a_template_still_reaches_its_recipient(): void
    {
        $customer = Customer::factory()->create();

        $customer->notify(new PlainNotification);

        $this->assertSame(
            1,
            Mail::mailer('array')->getSymfonyTransport()->messages()->count(),
            'The archive runs before the send, so a refused row used to cancel the email entirely'
        );
        $this->assertNull(Cache::get('notification_error'), 'Customer::notify() swallows the failure into a cache entry nobody reads');
    }

    public function test_an_email_without_a_template_is_archived(): void
    {
        $customer = Customer::factory()->create();

        $customer->notify(new PlainNotification);

        $this->assertDatabaseCount('email_messages', 1);
        $this->assertNull(EmailMessage::first()->template, 'An email built outside EmailTemplate has no template id to record');
    }

    public function test_an_archive_that_cannot_be_written_does_not_cancel_the_send(): void
    {
        $customer = Customer::factory()->create();

        $customer->notify(new PlainNotification(str_repeat('a', 300)));

        $this->assertSame(1, Mail::mailer('array')->getSymfonyTransport()->messages()->count());
        // The subject column is 255 long: this row cannot be written, and that is not a reason to drop the email.
        $this->assertDatabaseCount('email_messages', 0);
    }

    public function test_the_template_relation_points_at_the_right_column(): void
    {
        $customer = Customer::factory()->create();
        $template = EmailTemplate::where('name', 'custom')->first();
        $message = EmailMessage::factory()->create(['recipient_id' => $customer->id, 'template' => $template->id]);

        // The relation used to look for an email_template_id column that does not exist.
        $this->assertSame($template->id, $message->template()->first()?->id);
    }
}

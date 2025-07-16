<?php
/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Year: 2025
 */
namespace Client;

use App\Models\Account\Customer;
use App\Models\Account\EmailMessage;
use App\Models\Admin\EmailTemplate;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\StoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_emails_index(): void
    {
        $this->seed(EmailTemplateSeeder::class);
        $this->seed(StoreSeeder::class);
        Customer::factory(15)->create();
        EmailMessage::factory(15)->create();
        $user = $this->createCustomerModel();
        $this->actingAs($user)->get(route('front.emails.index'))->assertOk();
    }

    public function test_emails_search(): void
    {
        $this->seed(EmailTemplateSeeder::class);

        $this->seed(StoreSeeder::class);
        Customer::factory(15)->create();
        EmailMessage::factory(15)->create();
        $user = $this->createCustomerModel();
        $this->actingAs($user)->get(route('front.emails.index').'?search=notification')->assertOk();
    }

    public function test_email_can_show(): void
    {
        $this->seed(EmailTemplateSeeder::class);

        Customer::factory(15)->create();
        $this->seed(EmailTemplateSeeder::class);

        $email = EmailMessage::create([
            'recipient_id' => Customer::first()->id,
            'subject' => 'test',
            'content' => 'test',
            'recipient' => 'test@clientxcms.com',
            'template' => EmailTemplate::first()->id,
        ]);
        /** @var Customer $user */
        $user = Customer::first();
        $this->actingAs($user)->get(route('front.emails.show', ['email' => $email->id]))->assertOk();
    }

    public function test_email_cannot_show(): void
    {
        Customer::factory(15)->create();
        $this->seed(EmailTemplateSeeder::class);
        $email = EmailMessage::create([
            'recipient_id' => Customer::first()->id,
            'subject' => 'test',
            'content' => 'test',
            'recipient' => 'test@clientxcms.com',
            'template' => EmailTemplate::first()->id,
        ]);
        $user = Customer::where('id', '!=', $email->recipient_id)->first();
        $this->actingAs($user)->get(route('front.emails.show', ['email' => $email->id]))->assertNotFound();
    }
}

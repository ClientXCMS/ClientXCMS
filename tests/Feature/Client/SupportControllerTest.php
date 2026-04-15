<?php

namespace Tests\Feature\Client;

use App\Models\Helpdesk\SupportDepartment;
use App\Models\Helpdesk\SupportTicket;
use Database\Seeders\SupportDepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SupportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_support_index(): void
    {
        $user = $this->createCustomerModel();
        $ticket = $this->createTicketModel();
        $this->actingAs($user)->get(route('front.support'))->assertOk();
    }

    public function test_client_support_index_with_filter(): void
    {
        $user = $this->createCustomerModel();
        $ticket = $this->createTicketModel();
        $this->actingAs($user)->get(route('front.support', ['filter' => 'open']))->assertOk();
    }

    public function test_client_support_create(): void
    {
        $user = $this->createCustomerModel();
        $this->actingAs($user)->get(route('front.support.create'))->assertOk();
    }

    public function test_client_support_valid_store(): void
    {
        $user = $this->createCustomerModel();
        $department = $this->createDepartmentModel();
        $response = $this->actingAs($user)->post(route('front.support.create'), [
            'department_id' => $department->id,
            'subject' => 'Test Subject',
            'content' => 'Test content',
            'priority' => 'low',
        ]);
        $response->assertRedirect();

        // $this->assertDatabaseCount('support_tickets', 1);
        $this->assertCount(1, $user->tickets);
    }

    public function test_client_support_invalid_store(): void
    {
        $user = $this->createCustomerModel();
        $this->actingAs($user)->post(route('front.support.create'), [
            'department_id' => 30,
            'subject' => 'Test Subject',
            'content' => '',
        ])->assertSessionHasErrors();
    }

    public function test_client_support_invalid_related_type_store(): void
    {
        $user = $this->createCustomerModel();
        $this->actingAs($user)->post(route('front.support.create'), [
            'department_id' => 1,
            'subject' => 'Test Subject',
            'content' => 'Test content',
            'priority' => 'low',
            'related_id' => '1-test',
        ])->assertSessionHasErrors();
    }

    public function test_client_support_invalid_related_id_store(): void
    {
        $user = $this->createCustomerModel();
        $this->actingAs($user)->post(route('front.support.create'), [
            'department_id' => 1,
            'subject' => 'Test Subject',
            'content' => 'Test content',
            'priority' => 'low',
            'related_id' => 'service-30',
        ])->assertSessionHasErrors();
    }

    public function test_client_support_show(): void
    {
        $user = $this->createCustomerModel();
        $ticket = $this->createTicketModel($user->id);
        $this->actingAs($user)->get(route('front.support.show', $ticket->id))->assertOk();
    }

    public function test_client_support_show_with_invalid_customer(): void
    {
        $user = $this->createCustomerModel();
        $user2 = $this->createCustomerModel();
        $ticket = $this->createTicketModel($user2->id);
        $this->actingAs($user)->get(route('front.support.show', $ticket->id))->assertNotFound();
    }

    public function test_client_support_invalid_show(): void
    {
        $user = $this->createCustomerModel();
        $this->actingAs($user)->get(route('front.support.show', 30))->assertNotFound();
    }

    public function test_client_support_invalid_reply_show(): void
    {
        $user = $this->createCustomerModel();
        $ticket = $this->createTicketModel();
        $this->actingAs($user)->get(route('front.support.show', $ticket->id))->assertNotFound();
    }

    public function test_client_support_valid_reply(): void
    {
        $user = $this->createCustomerModel();
        $ticket = $this->createTicketModel($user->id);
        $this->actingAs($user)->post(route('front.support.reply', $ticket->id), [
            'content' => 'Test content',
        ])->assertRedirect();
        $ticket->refresh();
        // The ticket should have one message because the client replied to it and seeder haven't created any message
        $this->assertEquals(1, $ticket->messages()->count());
    }

    public function test_client_support_invalid_reply(): void
    {
        $user = $this->createCustomerModel();
        $ticket = $this->createTicketModel($user->id);
        $this->actingAs($user)->post(route('front.support.reply', $ticket->id), [
            'content' => '',
        ])->assertSessionHasErrors();
    }

    public function test_client_support_reply_with_attachment(): void
    {
        $user = $this->createCustomerModel();
        $ticket = $this->createTicketModel($user->id);
        $this->actingAs($user)->post(route('front.support.reply', $ticket->id), [
            'content' => 'Test content',
            'attachments' => [UploadedFile::fake()->image('test.jpg')],
        ])->assertRedirect();
        $ticket->refresh();
        // The ticket should have one attachment because the client replied to it and seeder haven't created any attachment
        $this->assertEquals(1, $ticket->attachments()->count());
    }

    public function test_client_support_close(): void
    {
        $user = $this->createCustomerModel();
        $ticket = $this->createTicketModel($user->id);
        $this->actingAs($user)->delete(route('front.support.close', $ticket))->assertRedirect();
        $ticket->refresh();
        $this->assertEquals(SupportTicket::STATUS_CLOSED, $ticket->status);
    }

    public function test_client_support_reopen(): void
    {
        $user = $this->createCustomerModel();
        $ticket = $this->createTicketModel($user->id);
        $ticket->close('customer', $user->id);
        $request = $this->actingAs($user)->post(route('front.support.reopen', $ticket));
        $request->assertRedirect();
        $request->assertSessionHas('success', __('helpdesk.support.ticket_reopened'));
        $ticket->refresh();
        $this->assertEquals(SupportTicket::STATUS_OPEN, $ticket->status);
    }

    public function test_client_support_reopen_with_expired_delay()
    {
        $user = $this->createCustomerModel();
        $ticket = $this->createTicketModel($user->id);
        $ticket->closed_at = now()->subDays(10);
        $ticket->save();
        $this->actingAs($user)->post(route('front.support.reopen', $ticket))->assertSessionHas('error', __('helpdesk.support.ticket_reopen_days', ['days' => 7]));
    }

    public function test_client_support_create_with_invalid_attachment(): void
    {
        $user = $this->createCustomerModel();
        $this->actingAs($user)->post(route('front.support.create'), [
            'department_id' => 1,
            'subject' => 'Test Subject',
            'content' => 'Test content',
            'priority' => 'low',
            'attachments' => [UploadedFile::fake()->create('test.php', 0)],
        ])->assertSessionHasErrors();
    }

    public function test_client_support_download_attachment(): void
    {
        $user = $this->createCustomerModel();
        $ticket = $this->createTicketModel($user->id);
        $ticket->addAttachment(UploadedFile::fake()->image('test.jpg'), $user->id);
        $this->actingAs($user)->get(route('front.support.download', [$ticket->id, $ticket->attachments()->first()->id]))->assertOk();
    }

    public function test_client_support_download_attachment_with_invalid_customer(): void
    {
        $user = $this->createCustomerModel();
        $ticket = $this->createTicketModel();
        $ticket->addAttachment(UploadedFile::fake()->image('test.jpg'), $user->id);
        $this->actingAs($user)->get(route('front.support.download', [$ticket->id, $ticket->attachments()->first()->id]))->assertNotFound();
    }

    private function createTicketModel(?int $customer = null)
    {
        $this->seed(SupportDepartmentSeeder::class);

        return SupportTicket::factory()->create([
            'customer_id' => $customer ?? $this->createCustomerModel()->id,
        ]);
    }

    private function createDepartmentModel()
    {
        return SupportDepartment::factory()->create();
    }
}

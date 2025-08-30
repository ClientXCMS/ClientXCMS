<?php

namespace Tests\Feature\Admin\Helpdesk;

use App\Models\Account\Customer;
use App\Models\Helpdesk\SupportDepartment;
use App\Models\Helpdesk\SupportTicket;
use Database\Seeders\SupportDepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TicketControllerTest extends TestCase
{
    use RefreshDatabase;

    const API_URL = 'admin/helpdesk/tickets';

    public function test_admin_support_index(): void
    {
        $ticket = $this->createTicketModel();
        $response = $this->performAdminAction('GET', self::API_URL);
        $response->assertStatus(200);
    }

    public function test_admin_support_index_with_filter(): void
    {
        $ticket = $this->createTicketModel();
        $response = $this->performAdminAction('GET', self::API_URL, ['filter' => ['priority' => 'open']]);
        $response->assertStatus(200);
    }

    public function test_admin_support_index_without_permission(): void
    {
        $response = $this->performAdminAction('GET', self::API_URL, [], ['admin.manage_products']);
        $response->assertStatus(403);
    }

    public function test_admin_support_create_part1(): void
    {
        $admin = $this->createAdminModel();
        $department = $this->createDepartmentModel();
        $this->performAdminAction('get', self::API_URL.'/create')->assertOk();
    }

    public function test_admin_support_create_part1_with_selected_department(): void
    {
        $user = $this->createCustomerModel();
        $department = $this->createDepartmentModel();
        $response = $this->performAdminAction('get', self::API_URL.'/create?department_id='.$department->id);
        $response->assertOk();
        $response->assertSee($department->name);
    }

    public function test_admin_support_create_part1_with_selected_customer(): void
    {
        $user = $this->createCustomerModel();
        $department = $this->createDepartmentModel();
        $response = $this->performAdminAction('get', self::API_URL.'/create?customer_id='.$user->id);
        $response->assertOk();
        $response->assertSee($user->firstname);
    }

    public function test_admin_support_create_part2(): void
    {
        $user = $this->createCustomerModel();
        $department = $this->createDepartmentModel();
        $response = $this->performAdminAction('post', self::API_URL.'/create?department_id='.$department->id.'&customer_id='.$user->id, [
            'department_id' => $department->id,
            'subject' => 'Test Subject',
            'content' => 'Test content',
            'priority' => 'low',
        ])->assertSessionHas('success')->assertRedirect();
        $this->assertCount(1, $user->tickets);
    }

    public function test_admin_support_show(): void
    {
        $admin = $this->createAdminModel();
        $ticket = $this->createTicketModel();
        $department = $this->createDepartmentModel();
        $response = $this->performAdminAction('GET', self::API_URL.'/'.$ticket->uuid);
        $response->assertStatus(200);
        $response->assertSee($ticket->subject);
    }

    public function test_admin_support_show_with_valid_allowed_department(): void
    {
        $admin = $this->createAdminModel();
        $ticket = $this->createTicketModel();
        $department = $this->createDepartmentModel();
        $response = $this->performAdminAction('GET', self::API_URL.'/'.$ticket->uuid, [], ['admin.manage_tickets_department.'.$ticket->department_id])->assertStatus(403);
        $response->assertStatus(403);
    }

    public function test_admin_support_show_with_invalid_allowed_department(): void
    {
        $admin = $this->createAdminModel();
        $ticket = $this->createTicketModel();
        $department = $this->createDepartmentModel();
        $department2 = $this->createDepartmentModel();
        $response = $this->performAdminAction('GET', self::API_URL.'/'.$ticket->uuid, [], ['admin.manage_tickets_department.'.$department2->id])->assertStatus(403);
        $response->assertStatus(403);
    }

    public function test_admin_support_update_message(): void
    {
        $admin = $this->createAdminModel();
        $ticket = $this->createTicketModel();
        $department = $this->createDepartmentModel();
        $ticket->addMessage('Test content', null, $admin->id);
        $message = $ticket->messages()->first();
        $response = $this->performAdminAction('POST', self::API_URL.'/'.$ticket->uuid.'/messages/'.$message->id.'/update', [
            'content' => 'Test content2',
        ]);
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('support_messages', [
            'id' => $message->id,
            'message' => 'Test content2',
        ]);
        $response->assertRedirect();
    }

    public function test_admin_support_update_message_with_invalid_permission(): void
    {
        $admin = $this->createAdminModel();
        $clone = $this->createAdminModel();
        $ticket = $this->createTicketModel();
        $department = $this->createDepartmentModel();
        $ticket->addMessage('Test content', null, $clone->id);
        $message = $ticket->messages()->first();
        $response = $this->performAdminAction('POST', self::API_URL.'/'.$ticket->uuid.'/messages/'.$message->id.'/update', [
            'content' => 'Test content2',
        ], ['admin.manage_tickets_department.'.$department->id]);
        $response->assertStatus(403);
    }

    public function test_admin_support_delete_message(): void
    {
        $admin = $this->createAdminModel();
        $ticket = $this->createTicketModel();
        $department = $this->createDepartmentModel();
        $ticket->addMessage('Test content', null, $admin->id);
        $message = $ticket->messages()->first();
        $response = $this->performAdminAction('DELETE', self::API_URL.'/'.$ticket->uuid.'/messages/'.$message->id.'/delete');
        $response->assertSessionHas('success');
    }

    public function test_admin_support_update(): void
    {
        $other = $this->createAdminModel();
        $ticket = $this->createTicketModel();
        $department = $this->createDepartmentModel();
        $response = $this->performAdminAction('PUT', route('admin.helpdesk.tickets.update', $ticket->id), [
            'department_id' => $department->id,
            'priority' => 'low',
            'subject' => 'Test Subject',
            'related_id' => 'none',
            'close_reason' => 'none',
            'assigned_to' => $other->id,
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('support_tickets', [
            'id' => $ticket->id,
            'department_id' => $department->id,
            'priority' => 'low',
            'subject' => 'Test Subject',
            'related_id' => null,
            'assigned_to' => $other->id,
        ]);
    }

    public function test_admin_support_valid_reply(): void
    {
        $ticket = $this->createTicketModel();
        $response = $this->performAdminAction('post', self::API_URL.'/'.$ticket->uuid.'/reply', [
            'content' => 'Test content3',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('support_messages', [
            'ticket_id' => $ticket->id,
            'message' => 'Test content3',
        ]);
    }

    public function test_admin_support_invalid_reply(): void
    {
        $ticket = $this->createTicketModel();
        $response = $this->performAdminAction('post', self::API_URL.'/'.$ticket->uuid.'/reply', [
            'content' => '',
        ]);
        $response->assertStatus(422);
    }

    public function test_admin_support_close(): void
    {
        $ticket = $this->createTicketModel();
        $response = $this->performAdminAction('delete', self::API_URL.'/'.$ticket->uuid);
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('support_tickets', [
            'id' => $ticket->id,
            'status' => 'closed',
        ]);
    }

    public function test_admin_support_reopen(): void
    {
        $ticket = $this->createTicketModel();
        $ticket->closed_at = now();
        $ticket->save();
        $response = $this->performAdminAction('post', self::API_URL.'/'.$ticket->uuid.'/reopen');
        $response->assertRedirect();
        $this->assertDatabaseHas('support_tickets', [
            'id' => $ticket->id,
            'status' => 'open',
        ]);
    }

    public function test_admin_support_reply_and_close(): void
    {
        $ticket = $this->createTicketModel();
        $response = $this->performAdminAction('post', self::API_URL.'/'.$ticket->uuid.'/reply', [
            'content' => 'Test content3',
            'close' => true,
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('support_messages', [
            'ticket_id' => $ticket->id,
            'message' => 'Test content3',
        ]);
        $this->assertDatabaseHas('support_tickets', [
            'id' => $ticket->id,
            'status' => 'closed',
        ]);
    }

    public function test_admin_support_download(): void
    {
        $ticket = $this->createTicketModel();
        $admin = $this->createAdminModel();
        $ticket->addAttachment(UploadedFile::fake()->image('test.jpg'), $ticket->customer_id);
        $attachment = $ticket->attachments()->first();
        $response = $this->performAdminAction('get', self::API_URL.'/'.$ticket->id.'/download/'.$attachment->id);
        $response->assertOk();
    }

    public function test_admin_support_reply_with_attachment(): void
    {
        $ticket = $this->createTicketModel();
        $response = $this->performAdminAction('post', self::API_URL.'/'.$ticket->uuid.'/reply', [
            'content' => 'Test content',
            'attachments' => [UploadedFile::fake()->image('test.jpg')],
        ]);
        $response->assertRedirect();
        $ticket->refresh();
        $this->assertEquals(1, $ticket->attachments()->count());
    }

    public function test_admin_support_download_invalid_attachment(): void
    {
        $admin = $this->createAdminModel();
        $ticket = $this->createTicketModel();
        $ticket->addAttachment(UploadedFile::fake()->image('test.jpg'), $ticket->customer_id);
        $ticket2 = $this->createTicketModel();
        $ticket2->addAttachment(UploadedFile::fake()->image('test.jpg'), $ticket->customer_id);
        $attachment = $ticket->attachments()->first();
        $response = $this->performAdminAction('get', self::API_URL.'/'.$ticket->uuid.'/download/'.$ticket2->id);
        $response->assertNotFound();
    }

    public function test_admin_support_show_invalid_ticket(): void
    {
        $admin = $this->createAdminModel();
        $response = $this->performAdminAction('get', self::API_URL.'/1000');
        $response->assertNotFound();
    }

    private function createTicketModel(?int $customer = null)
    {
        $this->seed(SupportDepartmentSeeder::class);
        if ($customer == null) {
            $customer = Customer::first()->id ?? $this->createCustomerModel()->id;
        }

        return SupportTicket::factory()->create([
            'customer_id' => $customer,
        ]);
    }

    public function test_admin_support_create_comment(): void
    {
        $ticket = $this->createTicketModel();
        $response = $this->performAdminAction('post', self::API_URL.'/'.$ticket->uuid.'/comments', [
            'comment' => 'Test content',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('support_comments', [
            'ticket_id' => $ticket->id,
            'comment' => 'Test content',
        ]);
    }

    private function createDepartmentModel()
    {
        return SupportDepartment::factory()->create();
    }
}

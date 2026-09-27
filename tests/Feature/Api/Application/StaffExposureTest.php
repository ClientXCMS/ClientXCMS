<?php

namespace Tests\Feature\Api\Application;

use App\Models\Account\Customer;
use App\Models\ActionLog;
use App\Models\Admin\Admin;
use App\Models\Helpdesk\SupportTicket;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class StaffExposureTest extends TestCase
{
    use RefreshDatabase;

    private const PUBLIC_FIELDS = ['firstname', 'id', 'lastname', 'username'];

    private Admin $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        $this->staff = Admin::first();
        $this->staff->forceFill(['last_login_ip' => '203.0.113.77', 'locale' => 'en_GB'])->save();
    }

    private function assertOnlyPublicStaffFields(TestResponse $response, array $staffObjects): void
    {
        $this->assertNotEmpty($staffObjects);
        foreach ($staffObjects as $staff) {
            $keys = array_keys($staff);
            sort($keys);
            $this->assertSame(self::PUBLIC_FIELDS, $keys);
            $this->assertSame($this->staff->username, $staff['username']);
        }
        $this->assertStringNotContainsString('203.0.113.77', $response->getContent());
        $this->assertStringNotContainsString($this->staff->email, $response->getContent());
    }

    public function test_ticket_reply_response_hides_staff_details(): void
    {
        Customer::factory()->create();
        $ticket = SupportTicket::factory()->create();
        $ticket->addMessage('Earlier staff reply', null, $this->staff->id);

        $response = $this->performAction('POST', 'api/application/tickets/'.$ticket->id.'/reply', ['tickets:reply'], [
            'content' => 'This is a reply message to the ticket',
        ]);

        $response->assertStatus(200);
        $this->assertOnlyPublicStaffFields($response, array_filter(array_column($response->json('ticket.messages'), 'admin')));
    }

    public function test_ticket_show_includes_hide_staff_details(): void
    {
        Customer::factory()->create();
        $ticket = SupportTicket::factory()->create();
        $ticket->forceFill(['assigned_to' => $this->staff->id])->save();
        $ticket->addMessage('Earlier staff reply', null, $this->staff->id);

        $response = $this->performAction('GET', 'api/application/tickets/'.$ticket->id.'?include=assignedTo,messages', ['tickets:show']);

        $response->assertStatus(200);
        $ticketData = $response->json('data.0');
        $this->assertOnlyPublicStaffFields($response, [$ticketData['assigned_to'], ...array_filter(array_column($ticketData['messages'], 'admin'))]);
    }

    public function test_action_log_staff_include_hides_staff_details(): void
    {
        ActionLog::log(ActionLog::RESOURCE_UPDATED, Customer::class, 1, $this->staff->id);

        $response = $this->performAction('GET', 'api/application/logs?include=staff', ['logs:index']);

        $response->assertStatus(200);
        $this->assertOnlyPublicStaffFields($response, array_filter(array_column($response->json('data'), 'staff')));
    }
}

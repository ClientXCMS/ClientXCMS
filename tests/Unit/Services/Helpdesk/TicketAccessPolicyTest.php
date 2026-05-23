<?php

namespace Tests\Unit\Services\Helpdesk;

use App\Models\Account\Customer;
use App\Models\Helpdesk\SupportAccessRule;
use App\Models\Helpdesk\SupportDepartment;
use App\Models\Provisioning\Service;
use App\Services\Helpdesk\TicketAccessPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketAccessPolicyTest extends TestCase
{
    use RefreshDatabase;

    private TicketAccessPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new TicketAccessPolicy;
    }

    public function test_no_rules_means_no_violations(): void
    {
        $customer = Customer::factory()->create();
        $this->assertSame([], $this->policy->violations($customer, 1, 'low'));
    }

    public function test_blocked_priority_rule_rejects_high(): void
    {
        $customer = Customer::factory()->create();
        SupportAccessRule::create([
            'name' => 'High priority is premium',
            'predicates' => ['block_priorities' => ['high']],
            'message_key' => 'v216::helpdesk.access.high_priority_restricted',
        ]);

        $this->assertNotEmpty($this->policy->violations($customer, 1, 'high'));
        $this->assertEmpty($this->policy->violations($customer, 1, 'low'));
        $this->assertEmpty($this->policy->violations($customer, 1, 'medium'));
    }

    public function test_requires_active_service_blocks_when_customer_has_none(): void
    {
        $customer = Customer::factory()->create();
        SupportAccessRule::create([
            'name' => 'Active service required',
            'predicates' => ['require_active_service' => true],
            'message_key' => 'v216::helpdesk.access.requires_active_service',
        ]);

        $violations = $this->policy->violations($customer, 1, 'low');
        $this->assertNotEmpty($violations);

        // Now create an active service — must pass.
        $this->createServiceModel($customer->id, Service::STATUS_ACTIVE);
        $this->assertEmpty($this->policy->violations($customer, 1, 'low'));
    }

    public function test_required_service_status_with_no_link_blocks(): void
    {
        $customer = Customer::factory()->create();
        SupportAccessRule::create([
            'name' => 'Must target an active linked service',
            'predicates' => [
                'require_service_status' => [Service::STATUS_ACTIVE],
                'require_related_type' => 'service',
            ],
            'message_key' => 'v216::helpdesk.access.requires_service_link',
        ]);

        $violations = $this->policy->violations($customer, 1, 'low');
        $this->assertNotEmpty($violations);

        // Active service linked → passes
        $service = $this->createServiceModel($customer->id, Service::STATUS_ACTIVE);
        $this->assertEmpty($this->policy->violations($customer, 1, 'low', 'service', $service->id));

        // Suspended service → still blocks
        $service->update(['status' => Service::STATUS_SUSPENDED]);
        $this->assertNotEmpty($this->policy->violations($customer, 1, 'low', 'service', $service->id));
    }

    public function test_scope_department_only_applies_to_matching_department(): void
    {
        $customer = Customer::factory()->create();
        $department = SupportDepartment::factory()->create();

        SupportAccessRule::create([
            'name' => 'High blocked in Sales only',
            'scope_department_id' => $department->id,
            'predicates' => ['block_priorities' => ['high']],
        ]);

        // Different department → rule does not apply.
        $this->assertEmpty($this->policy->violations($customer, 9999, 'high'));
        // Matching department → rule blocks.
        $this->assertNotEmpty($this->policy->violations($customer, $department->id, 'high'));
    }

    public function test_filter_departments_hides_those_with_blocking_rules(): void
    {
        $customer = Customer::factory()->create();
        $deptA = SupportDepartment::factory()->create();
        $deptB = SupportDepartment::factory()->create();

        SupportAccessRule::create([
            'name' => 'Dept A requires active service',
            'scope_department_id' => $deptA->id,
            'predicates' => ['require_active_service' => true],
        ]);

        $visible = $this->policy->filterDepartments(
            SupportDepartment::query()->whereIn('id', [$deptA->id, $deptB->id])->get(),
            $customer
        );

        $this->assertFalse($visible->contains('id', $deptA->id), 'A should be hidden');
        $this->assertTrue($visible->contains('id', $deptB->id), 'B should remain visible');
    }

    public function test_allowed_priorities_excludes_those_blocked(): void
    {
        $customer = Customer::factory()->create();
        SupportAccessRule::create([
            'name' => 'No high priority',
            'predicates' => ['block_priorities' => ['high']],
        ]);

        $allowed = $this->policy->allowedPriorities($customer, 1);
        $this->assertSame(['low', 'medium'], $allowed);
    }
}

<?php

namespace Tests\Feature\Admin\Provisioning;

use App\Models\Provisioning\CancellationReason;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancellationReasonControllerTest extends TestCase
{
    use RefreshDatabase;

    const API_URL = 'admin/cancellation_reasons';

    public function test_admin_cancellation_reason_index(): void
    {
        $this->seed(AdminSeeder::class);
        $request = $this->performAdminAction('GET', self::API_URL);
        $request->assertStatus(200);
    }

    public function test_admin_cancellation_reason_create(): void
    {
        $this->seed(AdminSeeder::class);
        $request = $this->performAdminAction('GET', self::API_URL.'/create');
        $request->assertStatus(200);
    }

    public function test_admin_cancellation_reason_store(): void
    {
        $this->seed(AdminSeeder::class);
        $request = $this->performAdminAction('POST', self::API_URL, [
            'reason' => 'Je ne suis pas satisfait du service',
            'status' => 'active',
        ]);
        $request->assertStatus(302);
        $this->assertDatabaseHas('cancellation_reasons', [
            'reason' => 'Je ne suis pas satisfait du service',
            'status' => 'active',
        ]);
    }

    public function test_admin_cancellation_reason_store_validation(): void
    {
        $this->seed(AdminSeeder::class);
        $request = $this->performAdminAction('POST', self::API_URL, [
            'reason' => '',
            'status' => 'invalid',
        ]);
        $request->assertStatus(422);
    }

    public function test_admin_cancellation_reason_show(): void
    {
        $this->seed(AdminSeeder::class);
        $reason = CancellationReason::create([
            'reason' => 'Test reason',
            'status' => 'active',
        ]);
        $request = $this->performAdminAction('GET', self::API_URL.'/'.$reason->id);
        $request->assertStatus(200);
        $request->assertSee('Test reason');
    }

    public function test_admin_cancellation_reason_update(): void
    {
        $this->seed(AdminSeeder::class);
        $reason = CancellationReason::create([
            'reason' => 'Old reason',
            'status' => 'active',
        ]);
        $request = $this->performAdminAction('PUT', self::API_URL.'/'.$reason->id, [
            'reason' => 'Updated reason',
            'status' => 'hidden',
        ]);
        $request->assertStatus(302);
        $this->assertDatabaseHas('cancellation_reasons', [
            'id' => $reason->id,
            'reason' => 'Updated reason',
            'status' => 'hidden',
        ]);
    }

    public function test_admin_cancellation_reason_destroy(): void
    {
        $this->seed(AdminSeeder::class);
        $reason = CancellationReason::create([
            'reason' => 'To be deleted',
            'status' => 'active',
        ]);
        $request = $this->performAdminAction('DELETE', self::API_URL.'/'.$reason->id);
        $request->assertStatus(302);
        $this->assertSoftDeleted('cancellation_reasons', [
            'id' => $reason->id,
        ]);
    }

    public function test_admin_cancellation_reason_analytics(): void
    {
        $this->seed(AdminSeeder::class);
        $request = $this->performAdminAction('GET', self::API_URL);
        $request->assertStatus(200);
    }

    public function test_admin_cancellation_reason_analytics_with_data(): void
    {
        $this->seed(AdminSeeder::class);
        $reason = CancellationReason::create([
            'reason' => 'Too expensive',
            'status' => 'active',
        ]);

        $customer = $this->createCustomerModel();
        $service = $this->createServiceModel($customer->id);
        $service->update([
            'cancelled_reason' => $reason->id,
            'cancelled_at' => now(),
            'status' => 'cancelled',
        ]);

        $request = $this->performAdminAction('GET', self::API_URL);
        $request->assertStatus(200);
        $request->assertSee('Too expensive');
    }

    public function test_admin_cancellation_reason_filter_by_status(): void
    {
        $this->seed(AdminSeeder::class);
        CancellationReason::create(['reason' => 'Active reason', 'status' => 'active']);
        CancellationReason::create(['reason' => 'Hidden reason', 'status' => 'hidden']);

        $request = $this->performAdminAction('GET', self::API_URL.'?filter[status]=active');
        $request->assertStatus(200);
    }

    public function test_admin_cancellation_reason_invalid_permission(): void
    {
        $this->seed(AdminSeeder::class);
        $request = $this->performAdminAction('GET', self::API_URL, [], ['admin.manage_products']);
        $request->assertStatus(403);
    }
}

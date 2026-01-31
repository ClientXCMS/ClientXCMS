<?php

namespace Tests\Feature\Api\Provisioning;

use App\Models\Provisioning\CancellationReason;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancellationReasonApiTest extends TestCase
{
    use RefreshDatabase;

    const API_URL = 'api/application/cancellation_reasons';

    public function test_api_cancellation_reason_index(): void
    {
        CancellationReason::create(['reason' => 'Test reason 1', 'status' => 'active']);
        CancellationReason::create(['reason' => 'Test reason 2', 'status' => 'active']);

        $response = $this->performAction('GET', self::API_URL, ['cancellation_reasons:index']);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'links',
        ]);
    }

    public function test_api_cancellation_reason_store(): void
    {
        $response = $this->performAction('POST', self::API_URL, ['cancellation_reasons:store'], [
            'reason' => 'New API reason',
            'status' => 'active',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'reason' => 'New API reason',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('cancellation_reasons', [
            'reason' => 'New API reason',
        ]);
    }

    public function test_api_cancellation_reason_store_validation(): void
    {
        $response = $this->performAction('POST', self::API_URL, ['cancellation_reasons:store'], [
            'reason' => '',
            'status' => 'invalid',
        ]);

        $response->assertStatus(422);
    }

    public function test_api_cancellation_reason_show(): void
    {
        $reason = CancellationReason::create([
            'reason' => 'Show reason',
            'status' => 'active',
        ]);

        $response = $this->performAction('GET', self::API_URL.'/'.$reason->id, ['cancellation_reasons:show']);
        $response->assertStatus(200);
        $response->assertJson([
            'id' => $reason->id,
            'reason' => 'Show reason',
        ]);
    }

    public function test_api_cancellation_reason_update(): void
    {
        $reason = CancellationReason::create([
            'reason' => 'Old reason',
            'status' => 'active',
        ]);

        $response = $this->performAction('POST', self::API_URL.'/'.$reason->id, ['cancellation_reasons:update'], [
            'reason' => 'Updated reason',
            'status' => 'hidden',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'reason' => 'Updated reason',
            'status' => 'hidden',
        ]);
    }

    public function test_api_cancellation_reason_destroy(): void
    {
        $reason = CancellationReason::create([
            'reason' => 'To delete',
            'status' => 'active',
        ]);

        $response = $this->performAction('DELETE', self::API_URL.'/'.$reason->id, ['cancellation_reasons:delete']);
        $response->assertStatus(200);
        $this->assertSoftDeleted('cancellation_reasons', ['id' => $reason->id]);
    }

    public function test_api_cancellation_reason_analytics(): void
    {
        $response = $this->performAction('GET', 'api/application/cancellation_reasons_analytics', ['cancellation_reasons:analytics']);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'start_date',
            'end_date',
            'total_cancellations',
            'distribution',
        ]);
    }

    public function test_api_cancellation_reason_analytics_with_data(): void
    {
        $reason = CancellationReason::create([
            'reason' => 'Price too high',
            'status' => 'active',
        ]);

        $customer = $this->createCustomerModel();
        $service = $this->createServiceModel($customer->id);
        $service->update([
            'cancelled_reason' => $reason->id,
            'cancelled_at' => now(),
            'status' => 'cancelled',
        ]);

        $response = $this->performAction('GET', 'api/application/cancellation_reasons_analytics', ['cancellation_reasons:analytics']);
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'total_cancellations' => 1,
        ]);
    }

    public function test_api_cancellation_reason_unauthorized(): void
    {
        $response = $this->getJson('/'.self::API_URL);
        $response->assertStatus(401);
    }
}

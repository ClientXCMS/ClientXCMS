<?php

namespace Tests\Feature\Admin;

use App\Models\Account\Customer;
use App\Models\Admin\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * v2.16 — Covers the bulk endpoints introduced by the
 * HandlesBulkActions trait on CustomerController. Each resource trait
 * dispatches via the same code path so a single resource's tests are
 * enough to exercise the validation + dispatch logic.
 */
class BulkActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_bulk_confirm_customers(): void
    {
        $admin = Admin::factory()->create();
        $customers = Customer::factory()->count(3)->create(['is_confirmed' => false]);

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.customers.bulk'), [
                'action' => 'confirm',
                'ids' => $customers->pluck('id')->all(),
            ]);

        $response->assertOk();
        $this->assertSame(3, $response->json('processed'));
        foreach ($customers as $c) {
            $c->refresh();
            $this->assertSame(1, (int) $c->is_confirmed);
        }
    }

    public function test_unknown_action_returns_422(): void
    {
        $admin = Admin::factory()->create();
        $customer = Customer::factory()->create();

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.customers.bulk'), [
                'action' => 'self_destruct',
                'ids' => [$customer->id],
            ]);

        $response->assertStatus(422);
    }

    public function test_bulk_rejects_empty_id_list(): void
    {
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.customers.bulk'), [
                'action' => 'confirm',
                'ids' => [],
            ]);

        $response->assertStatus(422);
    }
}

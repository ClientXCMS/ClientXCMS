<?php

namespace Tests\Feature\Admin\Store;

use App\Models\Billing\Gateway;
use Database\Seeders\GatewaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GatewayControllerRbacTest extends TestCase
{
    use RefreshDatabase;

    private function bankGateway(): Gateway
    {
        $this->seed(GatewaySeeder::class);

        return Gateway::where('uuid', 'bank_transfert')->firstOrFail();
    }

    public function test_save_config_blocks_admin_without_permission(): void
    {
        $gateway = $this->bankGateway();
        $response = $this->performAdminAction(
            'PUT',
            route('admin.settings.store.gateways.save', $gateway),
            [
                'status' => 'hidden',
                'name' => 'Pentest renamed',
                'minimal_amount' => 0,
            ],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);

        $gateway->refresh();
        $this->assertNotSame('Pentest renamed', $gateway->name, 'Gateway must not be renamed without permission');
    }

    public function test_save_config_allows_admin_with_manage_gateways_permission(): void
    {
        $gateway = $this->bankGateway();
        $response = $this->performAdminAction(
            'PUT',
            route('admin.settings.store.gateways.save', $gateway),
            [
                'status' => 'hidden',
                'name' => 'Pentest renamed legit',
                'minimal_amount' => 0,
            ],
            ['admin.manage_gateways']
        );
        $this->assertNotEquals(403, $response->status(), 'Admin with MANAGE_GATEWAYS must not be blocked');
        $gateway->refresh();
        $this->assertSame('Pentest renamed legit', $gateway->name);
    }
}

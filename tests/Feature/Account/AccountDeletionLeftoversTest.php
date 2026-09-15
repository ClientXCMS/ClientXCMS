<?php

namespace Tests\Feature\Account;

use App\Models\Account\Customer;
use App\Models\Account\CustomerAccountAccess;
use App\Models\Account\CustomerAccountInvitation;
use App\Models\ActionLog;
use App\Models\Admin\Setting;
use App\Models\Store\Basket\Basket;
use App\Services\Account\AccountDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountDeletionLeftoversTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_delegations_granted_by_the_account_are_dropped(): void
    {
        $owner = Customer::factory()->create();
        $sub = Customer::factory()->create();
        $this->grantAccess($owner, $sub);

        $this->deletionService()->delete($owner, true);

        // A sub-account keeping its delegation on a deleted account still sees it in its account switcher.
        $this->assertDatabaseCount('customer_account_accesses', 0);
    }

    public function test_the_delegations_received_by_the_account_are_dropped(): void
    {
        $owner = Customer::factory()->create();
        $sub = Customer::factory()->create();
        $this->grantAccess($owner, $sub);

        $this->deletionService()->delete($sub, true);

        // A deleted account must not keep its access to somebody else account.
        $this->assertDatabaseCount('customer_account_accesses', 0);
    }

    public function test_the_invitations_sent_by_the_account_are_dropped(): void
    {
        $owner = Customer::factory()->create();
        CustomerAccountInvitation::create([
            'owner_customer_id' => $owner->id,
            'email' => 'invited@example.com',
            'permissions' => ['service.show'],
            'all_services' => true,
        ]);

        $this->deletionService()->delete($owner, true);

        // A pending invitation holds the address of a third party and a token that still opens an account.
        $this->assertDatabaseCount('customer_account_invitations', 0);
    }

    public function test_the_basket_is_detached_and_anonymised(): void
    {
        $customer = Customer::factory()->create();
        $basket = Basket::create([
            'uuid' => 'basket-under-test',
            'user_id' => (string) $customer->id,
            'ip_address' => '203.0.113.7',
        ]);

        $this->deletionService()->delete($customer, true);

        $basket->refresh();
        $this->assertNull($basket->user_id, 'A basket kept under the customer id is never purged: the purge command only takes anonymous ones');
        $this->assertNull($basket->ip_address);
    }

    public function test_the_deletion_leaves_a_trace(): void
    {
        $customer = Customer::factory()->create();

        $this->deletionService()->delete($customer, true);

        $log = ActionLog::where('action', ActionLog::ACCOUNT_DELETED)->first();
        $this->assertNotNull($log, 'Without it, nobody can tell who deleted which account, nor when');
        $this->assertEquals($customer->id, $log->model_id);
    }

    public function test_the_trace_carries_no_identity(): void
    {
        $customer = Customer::factory()->create(['email' => 'gone@example.com', 'firstname' => 'Jean', 'lastname' => 'Dupont']);

        $this->deletionService()->delete($customer, true);

        $payload = json_encode(ActionLog::where('action', ActionLog::ACCOUNT_DELETED)->first()->payload);
        $this->assertStringNotContainsString('gone@example.com', $payload, 'Keeping the address in the trail puts back what the deletion just erased');
        $this->assertStringNotContainsString('Dupont', $payload);
    }

    public function test_the_automatic_purge_leaves_a_single_trace_without_the_address(): void
    {
        Setting::updateSettings(['gdpr_purge_inactive_days' => 30, 'gdpr_purge_skip_with_invoice' => false], null, false);
        $customer = Customer::factory()->create([
            'email' => 'inactive@example.com',
            'last_login' => now()->subDays(400),
            'created_at' => now()->subDays(400),
        ]);

        $this->artisan('purge:inactive-accounts')->assertSuccessful();

        $logs = ActionLog::where('action', ActionLog::ACCOUNT_DELETED)->get();
        $this->assertCount(1, $logs, 'The purge used to write its own entry on top of the one the service writes');
        $this->assertStringNotContainsString('inactive@example.com', json_encode($logs->first()->payload));
        $this->assertSame('gdpr_inactive', $logs->first()->payload['reason'] ?? null);
        $this->assertEquals($customer->id, $logs->first()->model_id);
    }

    private function grantAccess(Customer $owner, Customer $sub): void
    {
        CustomerAccountAccess::create([
            'owner_customer_id' => $owner->id,
            'sub_customer_id' => $sub->id,
            'created_by_customer_id' => $owner->id,
            'permissions' => ['service.show'],
            'all_services' => true,
        ]);
    }

    private function deletionService(): AccountDeletionService
    {
        return app(AccountDeletionService::class);
    }
}

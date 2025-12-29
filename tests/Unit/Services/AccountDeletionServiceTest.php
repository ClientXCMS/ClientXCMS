<?php

namespace Tests\Unit\Services;

use App\Models\Account\Customer;
use App\Models\Billing\Invoice;
use App\Models\Helpdesk\SupportTicket;
use App\Models\Provisioning\Service;
use App\Services\Account\AccountDeletionException;
use App\Services\Account\AccountDeletionService;
use Database\Factories\Helpdesk\DepartmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountDeletionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AccountDeletionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AccountDeletionService();
    }

    protected function createCustomer(array $attributes = []): Customer
    {
        return Customer::create(array_merge([
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'test@example.com',
            'password' => 'password',
            'address' => '123 Test St',
            'city' => 'Test City',
            'country' => 'FR',
            'region' => 'Test Region',
            'zipcode' => '12345',
            'phone' => '0612345678',
        ], $attributes));
    }

    public function test_can_delete_customer_without_blocking_reasons(): void
    {
        $customer = $this->createCustomer();


        $this->assertTrue($this->service->canDelete($customer));
        $this->assertEmpty($this->service->getBlockingReasons($customer));
    }

    public function test_cannot_delete_customer_with_active_services(): void
    {
        $customer = $this->createCustomer();

        // Create an active service
        Service::create([
            'customer_id' => $customer->id,
            'name' => 'Test Service',
            'status' => Service::STATUS_ACTIVE,
            'type' => 'none',
            'price' => 10.00,
            'billing' => 'monthly',
            'initial_price' => 10.00,
            'expires_at' => now()->addMonth(),
        ]);

        $this->assertFalse($this->service->canDelete($customer));

        $reasons = $this->service->getBlockingReasons($customer);
        $this->assertArrayHasKey('active_services', $reasons);
        $this->assertEquals(1, $reasons['active_services']['count']);
    }

    public function test_cannot_delete_customer_with_pending_services(): void
    {
        $customer = $this->createCustomer();

        Service::create([
            'customer_id' => $customer->id,
            'name' => 'Pending Service',
            'status' => Service::STATUS_PENDING,
            'type' => 'none',
            'price' => 10.00,
            'billing' => 'monthly',
            'initial_price' => 10.00,
            'expires_at' => now()->addMonth(),
        ]);

        $this->assertFalse($this->service->canDelete($customer));

        $reasons = $this->service->getBlockingReasons($customer);
        $this->assertArrayHasKey('active_services', $reasons);
    }

    public function test_cannot_delete_customer_with_suspended_services(): void
    {
        $customer = $this->createCustomer();

        Service::create([
            'customer_id' => $customer->id,
            'name' => 'Suspended Service',
            'status' => Service::STATUS_SUSPENDED,
            'type' => 'none',
            'price' => 10.00,
            'billing' => 'monthly',
            'initial_price' => 10.00,
            'expires_at' => now()->addMonth(),
        ]);

        $this->assertFalse($this->service->canDelete($customer));
    }

    public function test_can_delete_customer_with_expired_services(): void
    {
        $customer = $this->createCustomer();

        Service::create([
            'customer_id' => $customer->id,
            'name' => 'Expired Service',
            'status' => Service::STATUS_EXPIRED,
            'type' => 'none',
            'price' => 10.00,
            'billing' => 'monthly',
            'initial_price' => 10.00,
            'expires_at' => now()->subMonth(),
        ]);

        $this->assertTrue($this->service->canDelete($customer));
    }

    public function test_can_delete_customer_with_cancelled_services(): void
    {
        $customer = $this->createCustomer();

        Service::create([
            'customer_id' => $customer->id,
            'name' => 'Cancelled Service',
            'status' => Service::STATUS_CANCELLED,
            'type' => 'none',
            'price' => 10.00,
            'billing' => 'monthly',
            'initial_price' => 10.00,
            'expires_at' => now()->subMonth(),
        ]);

        $this->assertTrue($this->service->canDelete($customer));
    }

    public function test_delete_throws_exception_when_blocking_reasons_exist(): void
    {
        $customer = $this->createCustomer();

        Service::create([
            'customer_id' => $customer->id,
            'name' => 'Active Service',
            'status' => Service::STATUS_ACTIVE,
            'type' => 'none',
            'price' => 10.00,
            'billing' => 'monthly',
            'initial_price' => 10.00,
            'expires_at' => now()->addMonth(),
        ]);

        $this->expectException(AccountDeletionException::class);
        $this->service->delete($customer);
    }

    public function test_delete_with_force_bypasses_blocking_reasons(): void
    {
        $customer = $this->createCustomer();

        Service::create([
            'customer_id' => $customer->id,
            'name' => 'Active Service',
            'status' => Service::STATUS_ACTIVE,
            'type' => 'none',
            'price' => 10.00,
            'billing' => 'monthly',
            'initial_price' => 10.00,
            'expires_at' => now()->addMonth(),
        ]);

        $result = $this->service->delete($customer, force: true);

        $this->assertTrue($result);
        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_delete_soft_deletes_customer(): void
    {
        $customer = $this->createCustomer();

        $this->service->delete($customer);

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_delete_nullifies_invoice_customer_id(): void
    {
        $customer = $this->createCustomer();

        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'total' => 100.00,
            'subtotal' => 100.00,
            'tax' => 0.00,
            'setupfees' => 0.00,
            'currency' => 'EUR',
            'status' => 'paid',
            'due_date' => now(),
            'notes' => '',
        ]);

        $this->service->delete($customer);

        $this->assertNull($invoice->fresh()->customer_id);
    }

    public function test_delete_closes_open_tickets(): void
    {
        $customer = $this->createCustomer();

        SupportTicket::create([
            'customer_id' => $customer->id,
            'subject' => 'Test Ticket',
            'status' => 'open',
            'department_id' => DepartmentFactory::new()->create()->id,
        ]);

        $this->service->delete($customer);

        $this->assertDatabaseMissing('support_tickets', ['status' => 'closed', 'customer_id' => $customer->id]);
        $ticket = SupportTicket::first();
        $this->assertNotNull($ticket->closed_at);
    }

    public function test_delete_revokes_api_tokens(): void
    {
        $customer = $this->createCustomer();
        $customer->createToken('test-token');

        $this->assertEquals(1, $customer->tokens()->count());

        $this->service->delete($customer);

        $this->assertEquals(0, $customer->tokens()->count());
    }

    public function test_delete_clears_metadata(): void
    {
        $customer = $this->createCustomer();
        $customer->attachMetadata('test_key', 'test_value');

        $this->assertEquals(1, $customer->metadata()->count());

        $this->service->delete($customer);

        $this->assertEquals(0, $customer->metadata()->count());
    }

    public function test_delete_disables_2fa(): void
    {
        $customer = $this->createCustomer();
        $customer->twoFactorEnable('test_secret');

        $this->assertTrue($customer->twoFactorEnabled());

        $this->service->delete($customer);

        // After deletion, 2FA metadata should be cleared
        $this->assertEquals(0, $customer->metadata()->count());
    }

    public function test_format_blocking_reasons_with_active_services(): void
    {
        $reasons = [
            'active_services' => [
                'count' => 3,
                'services' => [1 => 'Service A', 2 => 'Service B', 3 => 'Service C'],
            ],
        ];

        $formatted = $this->service->formatBlockingReasons($reasons);

        $this->assertStringContainsString('3', $formatted);
    }

    public function test_format_blocking_reasons_with_pending_invoices(): void
    {
        $reasons = [
            'pending_invoices' => [
                'count' => 2,
                'invoices' => [1 => 'INV-001', 2 => 'INV-002'],
            ],
        ];

        $formatted = $this->service->formatBlockingReasons($reasons);

        $this->assertStringContainsString('2', $formatted);
    }

    public function test_format_blocking_reasons_with_multiple_reasons(): void
    {
        $reasons = [
            'active_services' => [
                'count' => 1,
                'services' => [1 => 'Service A'],
            ],
            'pending_invoices' => [
                'count' => 1,
                'invoices' => [1 => 'INV-001'],
            ],
        ];

        $formatted = $this->service->formatBlockingReasons($reasons);

        $this->assertNotEmpty($formatted);
    }

    public function test_account_deletion_exception_contains_blocking_reasons(): void
    {
        $reasons = ['active_services' => ['count' => 1]];
        $exception = new AccountDeletionException('Test message', $reasons);

        $this->assertEquals($reasons, $exception->getBlockingReasons());
    }

    public function test_deleted_user_placeholder_returns_string(): void
    {
        $placeholder = AccountDeletionService::getDeletedUserPlaceholder();

        $this->assertIsString($placeholder);
        $this->assertNotEmpty($placeholder);
    }
}

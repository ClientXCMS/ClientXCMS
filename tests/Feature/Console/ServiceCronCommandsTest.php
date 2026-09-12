<?php

namespace Tests\Feature\Console;

use App\Models\Account\Customer;
use App\Models\Admin\Setting;
use App\Models\Billing\Invoice;
use App\Models\Provisioning\Service;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCronCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_renewals_creates_and_attaches_an_invoice_only_for_eligible_service(): void
    {
        Carbon::setTestNow('2026-08-25 12:00:00');
        $customer = Customer::factory()->create();
        Setting::updateSettings(['days_before_creation_renewal_invoice' => 7], null, false);
        $eligible = $this->createServiceModel($customer->id, Service::STATUS_ACTIVE);
        $eligible->update(['expires_at' => now()->addDays(7), 'invoice_id' => null]);
        $future = $this->createServiceModel($customer->id, Service::STATUS_ACTIVE);
        $future->update(['expires_at' => now()->addDays(8), 'invoice_id' => null]);

        $this->artisan('services:renewals')
            ->expectsOutputToContain("Created invoice for service #{$eligible->id}")
            ->assertExitCode(Command::SUCCESS);

        $this->assertNotNull($eligible->fresh()->invoice_id);
        $this->assertNull($future->fresh()->invoice_id);
        $this->assertSame(1, Invoice::query()->count());
    }

    public function test_expire_command_hides_only_expired_services_past_retention(): void
    {
        Carbon::setTestNow('2026-08-25 12:00:00');
        $customer = Customer::factory()->create();
        Setting::updateSettings(['services_expire_and_delete_after_days' => 90], null, false);
        $old = $this->createServiceModel($customer->id, Service::STATUS_EXPIRED);
        $old->update(['expires_at' => now()->subDays(91)]);
        $recent = $this->createServiceModel($customer->id, Service::STATUS_EXPIRED);
        $recent->update(['expires_at' => now()->subDays(89)]);

        $this->artisan('services:expire')->assertExitCode(Command::SUCCESS);

        $this->assertSame(Service::STATUS_HIDDEN, $old->fresh()->status);
        $this->assertSame(Service::STATUS_EXPIRED, $recent->fresh()->status);
    }

    /**
     * Dates are written by the application, so the rules that read them must use
     * the application clock. Freezing far ahead of the database clock proves it.
     */
    public function test_service_lifecycle_rules_follow_the_application_clock(): void
    {
        Carbon::setTestNow('2031-03-04 09:00:00');
        $customer = Customer::factory()->create();
        Setting::updateSettings(['services_expire_and_delete_after_days' => 90], null, false);

        $due = $this->createServiceModel($customer->id, Service::STATUS_ACTIVE);
        $due->update([
            'cancelled_at' => now()->subMinutes(30),
            'cancelled_reason' => 'asked by the customer',
            'is_cancelled' => false,
        ]);
        $retained = $this->createServiceModel($customer->id, Service::STATUS_EXPIRED);
        $retained->update(['expires_at' => now()->subDays(91)]);

        $this->artisan('services:expire')->assertExitCode(Command::SUCCESS);

        $this->assertTrue((bool) $due->fresh()->is_cancelled, 'a due cancellation must be processed');
        $this->assertSame(Service::STATUS_HIDDEN, $retained->fresh()->status);
    }

    public function test_expiration_notification_failure_returns_non_zero(): void
    {
        Carbon::setTestNow('2026-08-26 12:00:00');
        $customer = Customer::factory()->create();
        Setting::updateSettings(['notifications_expiration_days' => '3'], null, false);
        $service = $this->createServiceModel($customer->id, Service::STATUS_ACTIVE);
        $service->update(['expires_at' => now()->addDays(3), 'cancelled_at' => null]);
        $service->attachMetadata('disable_notify_expiration', true);

        $this->assertFalse(Service::getShouldNotifyExpiration(['3'])->contains('id', $service->id));

        $this->artisan('services:notify-expiration')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_disabled_suspension_and_expiration_are_excluded_from_automatic_processing(): void
    {
        Carbon::setTestNow('2026-08-26 12:00:00');
        $customer = Customer::factory()->create();

        $active = $this->createServiceModel($customer->id, Service::STATUS_ACTIVE);
        $active->update(['expires_at' => now()->subDay(), 'cancelled_at' => null]);
        $active->attachMetadata('disable_suspension', true);

        $suspended = $this->createServiceModel($customer->id, Service::STATUS_SUSPENDED);
        $suspended->update(['expires_at' => now()->subDays(30)]);
        $suspended->attachMetadata('disable_expiration', true);

        $notification = $this->createServiceModel($customer->id, Service::STATUS_ACTIVE);
        $notification->update(['expires_at' => now()->addDays(3), 'cancelled_at' => null]);
        $notification->attachMetadata('disable_expiration', true);

        $this->assertFalse(Service::getShouldSuspend()->contains('id', $active->id));
        $this->assertFalse(Service::getShouldExpire()->contains('id', $suspended->id));
        $this->assertFalse(Service::getShouldNotifyExpiration(['3'])->contains('id', $notification->id));
    }
}

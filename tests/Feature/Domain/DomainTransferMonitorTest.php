<?php

namespace Tests\Feature\Domain;

use App\Abstracts\AbstractDomainRegistrar;
use App\Core\Domain\DomainTransferStatus;
use App\Core\Domain\FakeDomainRegistrar;
use App\DTO\Domain\DomainAvailabilityDTO;
use App\DTO\Domain\DomainTransferStatusDTO;
use App\DTO\Provisioning\ConnectionResponse;
use App\DTO\Provisioning\ServiceStateChangeDTO;
use App\Events\Core\Service\ServiceDelivered;
use App\Events\Core\Service\ServiceDeliveryFailed;
use App\Models\Account\Customer;
use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;
use App\Services\Domain\DomainRegistrarManager;
use App\Services\Domain\DomainTransferMonitor;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class DomainTransferMonitorTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, DomainTransferStatusDTO|RuntimeException> Registrar answers keyed by domain name. */
    private array $answers = [];

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([ServiceDelivered::class, ServiceDeliveryFailed::class]);
        Customer::factory()->create();
        $answers = &$this->answers;
        $manager = app(DomainRegistrarManager::class);
        $manager->register(new class($answers) extends FakeDomainRegistrar
        {
            public function __construct(private array &$answers) {}

            public function uuid(): string
            {
                return 'transfer-test';
            }

            public function transferStatus(Service $service): DomainTransferStatusDTO
            {
                $answer = $this->answers[$service->name];
                if ($answer instanceof RuntimeException) {
                    throw $answer;
                }

                return $answer;
            }
        });
        $manager->register(new UntrackedTransferRegistrar);
    }

    public function test_only_pending_domain_transfers_are_monitored(): void
    {
        $transfer = $this->transfer('example.com');
        $this->transfer('active.com', ['status' => Service::STATUS_ACTIVE]);
        $this->transfer('register.com', ['data' => ['registrar_status' => 'pending']]);
        $this->transfer('hosting.com', ['type' => 'none']);

        $this->assertSame([$transfer->id], app(DomainTransferMonitor::class)->pendingServices()->pluck('id')->all());
    }

    public function test_completed_transfer_activates_and_delivers_the_service(): void
    {
        $service = $this->transfer('example.com');
        $this->answers['example.com'] = new DomainTransferStatusDTO(DomainTransferStatus::Completed);

        $status = app(DomainTransferMonitor::class)->check($service);

        $service->refresh();
        $this->assertSame(DomainTransferStatus::Completed, $status);
        $this->assertSame(Service::STATUS_ACTIVE, $service->status);
        $this->assertSame('active', $service->data['registrar_status']);
        $this->assertNull($service->delivery_errors);
        Event::assertDispatched(ServiceDelivered::class, fn ($event) => $event->dto->message === 'Domain transfer completed');
    }

    public function test_failed_transfer_stays_pending_and_can_be_resubmitted(): void
    {
        $service = $this->transfer('example.com');
        $this->answers['example.com'] = new DomainTransferStatusDTO(DomainTransferStatus::Failed, 'Invalid auth code');

        app(DomainTransferMonitor::class)->check($service);

        $service->refresh();
        $this->assertSame(Service::STATUS_PENDING, $service->status);
        $this->assertSame('transfer_failed', $service->data['registrar_status']);
        $this->assertSame('Invalid auth code', $service->data['transfer_error']);
        $this->assertSame('Invalid auth code', $service->delivery_errors);
        $this->assertArrayNotHasKey('transfer_submitted', $service->data);
        $this->assertArrayNotHasKey('registrar_id', $service->data);
        Event::assertDispatched(ServiceDeliveryFailed::class);
        Event::assertNotDispatched(ServiceDelivered::class);
    }

    public function test_pending_transfer_and_unsupported_registrar_leave_the_service_untouched(): void
    {
        $pending = $this->transfer('example.com');
        $untracked = $this->transfer('untracked.com', [], 'untracked');
        $this->answers['example.com'] = new DomainTransferStatusDTO(DomainTransferStatus::Pending);
        $monitor = app(DomainTransferMonitor::class);

        $this->assertSame(DomainTransferStatus::Pending, $monitor->check($pending));
        $this->assertNull($monitor->check($untracked));
        foreach ([$pending, $untracked] as $service) {
            $this->assertSame('transfer_pending', $service->refresh()->data['registrar_status']);
            $this->assertSame(Service::STATUS_PENDING, $service->status);
        }
    }

    public function test_command_reports_registrar_errors_without_blocking_other_transfers(): void
    {
        $failing = $this->transfer('down.com');
        $completed = $this->transfer('example.com');
        $this->answers['down.com'] = new RuntimeException('Registrar is unreachable');
        $this->answers['example.com'] = new DomainTransferStatusDTO(DomainTransferStatus::Completed);

        $this->artisan('services:domain-transfers')->assertFailed();

        $this->assertSame(Service::STATUS_PENDING, $failing->refresh()->status);
        $this->assertSame(Service::STATUS_ACTIVE, $completed->refresh()->status);
    }

    public function test_transfer_goes_from_delivery_to_activation_with_the_fake_registrar(): void
    {
        $server = Server::factory()->create(['type' => 'domain', 'hostname' => 'fake']);
        $service = Service::factory()->create([
            'name' => 'example.com', 'type' => 'domain', 'status' => Service::STATUS_PENDING, 'server_id' => $server->id,
            'data' => ['domain' => 'example.com', 'operation' => 'transfer', 'auth_code' => 'EPP-123', 'nameservers' => ['ns1.example.com', 'ns2.example.com']],
        ]);

        $delivery = $service->deliver();

        $this->assertTrue($delivery->success, $delivery->message);
        $this->assertSame(Service::STATUS_PENDING, $service->refresh()->status);
        $this->assertSame('transfer_pending', $service->data['registrar_status']);
        $this->assertArrayNotHasKey('auth_code', $service->data);
        Event::assertNotDispatched(ServiceDelivered::class);

        $this->artisan('services:domain-transfers')->assertSuccessful();

        $this->assertSame(Service::STATUS_ACTIVE, $service->refresh()->status);
        Event::assertDispatchedTimes(ServiceDelivered::class, 1);
    }

    public function test_immediate_delivery_still_activates_and_notifies(): void
    {
        $service = Service::factory()->create(['type' => 'none', 'status' => Service::STATUS_PENDING]);

        $delivery = $service->deliver();

        $this->assertTrue($delivery->success, $delivery->message);
        $this->assertSame(Service::STATUS_ACTIVE, $service->refresh()->status);
        Event::assertDispatched(ServiceDelivered::class, fn ($event) => $event->service->is($service));
    }

    private function transfer(string $domain, array $attributes = [], string $registrar = 'transfer-test'): Service
    {
        $server = Server::factory()->create(['type' => 'domain', 'hostname' => $registrar]);
        $data = array_merge(['domain' => $domain, 'operation' => 'transfer', 'registrar_status' => 'transfer_pending', 'transfer_submitted' => true, 'registrar_id' => 'R-1'], $attributes['data'] ?? []);

        return Service::factory()->create(array_merge([
            'name' => $domain, 'type' => 'domain', 'status' => Service::STATUS_PENDING, 'server_id' => $server->id,
        ], $attributes, ['data' => $data]));
    }
}

/** A registrar that cannot report transfer progress. */
class UntrackedTransferRegistrar extends AbstractDomainRegistrar
{
    public function uuid(): string
    {
        return 'untracked';
    }

    public function title(): string
    {
        return 'Untracked';
    }

    public function testConnection(array $params): ConnectionResponse
    {
        return new ConnectionResponse(new Response(200));
    }

    public function checkAvailability(string $domain): DomainAvailabilityDTO
    {
        return new DomainAvailabilityDTO($domain, true);
    }

    public function register(Service $service): ServiceStateChangeDTO
    {
        return $this->unsupported($service);
    }

    public function renew(Service $service, int $years): ServiceStateChangeDTO
    {
        return $this->unsupported($service);
    }
}

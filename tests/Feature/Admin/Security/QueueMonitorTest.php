<?php

namespace Tests\Feature\Admin\Security;

use App\Http\Middleware\RequireAdminPassword;
use App\Jobs\QueueWorkerHeartbeatJob;
use App\Models\ActionLog;
use App\Models\Admin\Admin;
use App\Models\Admin\Setting;
use App\Models\FailedQueueJob;
use App\Models\QueueJob;
use App\Models\QueueWorkerHeartbeat;
use App\Services\Core\QueueHealthService;
use App\Services\Core\ScheduledTasksHealthService;
use Carbon\Carbon;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueMonitorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'queue.default' => 'database',
            'queue-monitor.connection' => 'database',
            'queue-monitor.queue' => 'default',
            'queue-monitor.heartbeat_after_minutes' => 5,
            'queue-monitor.waiting_after_minutes' => 15,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_heartbeat_is_dispatched_and_processed(): void
    {
        Queue::fake();
        $service = app(QueueHealthService::class);
        $service->dispatchHeartbeat();
        $heartbeat = QueueWorkerHeartbeat::firstOrFail();

        Queue::assertPushed(QueueWorkerHeartbeatJob::class, fn ($job) => $job->token === $heartbeat->token);
        $this->assertNull($heartbeat->processed_at);

        (new QueueWorkerHeartbeatJob($heartbeat->token))->handle();
        $this->assertNotNull($heartbeat->fresh()->processed_at);
    }

    public function test_heartbeat_becomes_stale_only_after_five_minutes(): void
    {
        Carbon::setTestNow('2026-08-26 12:00:00');
        $service = app(QueueHealthService::class);
        $this->assertTrue($service->heartbeatIsStale());

        QueueWorkerHeartbeat::create([
            'connection' => 'database', 'queue' => 'default', 'processed_at' => now()->subMinutes(5),
        ]);
        $this->assertFalse($service->heartbeatIsStale());

        Carbon::setTestNow(now()->addSecond());
        $this->assertTrue($service->heartbeatIsStale());
    }

    public function test_sync_and_unsupported_drivers_do_not_raise_worker_alerts(): void
    {
        config(['queue-monitor.connection' => 'sync']);
        $service = app(QueueHealthService::class);
        $this->assertTrue($service->isSynchronous());
        $this->assertFalse($service->heartbeatIsStale());

        config(['queue-monitor.connection' => 'redis']);
        $this->assertFalse($service->isManageable());
        $this->assertFalse($service->heartbeatIsStale());
    }

    public function test_waiting_delayed_and_reserved_boundaries_are_classified(): void
    {
        Carbon::setTestNow('2026-08-26 12:00:00');
        $service = app(QueueHealthService::class);
        $atLimit = $this->createJob(now()->subMinutes(15)->timestamp, now()->subMinute()->timestamp);
        $blocked = $this->createJob(now()->subMinutes(15)->subSecond()->timestamp, now()->subMinute()->timestamp);
        $delayed = $this->createJob(now()->subHour()->timestamp, now()->addMinute()->timestamp);
        $reserved = $this->createJob(now()->subHour()->timestamp, now()->subMinute()->timestamp, now()->subSeconds(91)->timestamp);

        $this->assertFalse($service->blockedWaitingQuery()->whereKey($atLimit)->exists());
        $this->assertTrue($service->blockedWaitingQuery()->whereKey($blocked)->exists());
        $this->assertTrue($service->delayedQuery()->whereKey($delayed)->exists());
        $this->assertTrue($service->staleReservedQuery()->whereKey($reserved)->exists());
    }

    public function test_page_escapes_failed_exception_and_hides_raw_payload(): void
    {
        $job = FailedQueueJob::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'Safe Job', 'secret' => 'never-show-this']),
            'exception' => '<script>alert(1)</script>'.str_repeat('x', 500),
            'failed_at' => now(),
        ]);

        $response = $this->performAdminAction('GET', route('admin.queues.index', ['status' => 'failed']), [], ['admin.show_logs']);
        $response->assertOk()->assertSee('Safe Job')->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
        $response->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('never-show-this');
        $this->assertNotNull($job);
    }

    public function test_page_is_forbidden_without_logs_permission(): void
    {
        $this->performAdminAction('GET', route('admin.queues.index'), [], ['admin.manage_settings'])->assertForbidden();
    }

    public function test_mutations_require_recent_password_confirmation(): void
    {
        $id = $this->createJob(now()->timestamp, now()->timestamp);
        $this->seed(AdminSeeder::class);
        $this->actingAs(Admin::first(), 'admin');

        $this->post(route('admin.queues.action'), ['action' => 'delete_pending', 'ids' => [(string) $id]])
            ->assertRedirect(route('admin.password.confirm'));
        $this->assertDatabaseHas('jobs', ['id' => $id]);
    }

    public function test_pending_jobs_can_be_released_made_available_and_deleted_with_logs(): void
    {
        Carbon::setTestNow('2026-08-26 12:00:00');
        $delayed = $this->createJob(now()->timestamp, now()->addHour()->timestamp);
        $reserved = $this->createJob(now()->timestamp, now()->timestamp, now()->timestamp);

        $this->withSession([RequireAdminPassword::SESSION_KEY => time()]);
        $this->performAdminAction('POST', route('admin.queues.action'), [
            'action' => 'make_available', 'ids' => [(string) $delayed],
        ], ['admin.show_logs'])->assertRedirect();
        $this->assertSame(now()->timestamp, QueueJob::findOrFail($delayed)->available_at);

        session()->put(RequireAdminPassword::SESSION_KEY, time());
        $this->post(route('admin.queues.action'), ['action' => 'release', 'ids' => [(string) $reserved]])->assertRedirect();
        $this->assertNull(QueueJob::findOrFail($reserved)->reserved_at);

        session()->put(RequireAdminPassword::SESSION_KEY, time());
        $this->post(route('admin.queues.action'), ['action' => 'delete_pending', 'ids' => [(string) $delayed]])->assertRedirect();
        $this->assertDatabaseMissing('jobs', ['id' => $delayed]);
        $this->assertGreaterThanOrEqual(3, ActionLog::where('model', QueueJob::class)->count());
    }

    public function test_failed_jobs_can_be_retried_and_deleted(): void
    {
        $retry = $this->createFailedJob('Retry Job');
        $delete = $this->createFailedJob('Delete Job');
        $this->withSession([RequireAdminPassword::SESSION_KEY => time()]);

        $this->performAdminAction('POST', route('admin.queues.action'), [
            'action' => 'retry_failed', 'ids' => [$retry],
        ], ['admin.show_logs'])->assertRedirect();
        $this->assertDatabaseMissing('failed_jobs', ['uuid' => $retry]);
        $this->assertDatabaseCount('jobs', 1);

        session()->put(RequireAdminPassword::SESSION_KEY, time());
        $this->post(route('admin.queues.action'), ['action' => 'delete_failed', 'ids' => [$delete]])->assertRedirect();
        $this->assertDatabaseMissing('failed_jobs', ['uuid' => $delete]);
    }

    public function test_action_ignores_a_job_whose_state_no_longer_matches(): void
    {
        $reserved = $this->createJob(now()->timestamp, now()->timestamp, now()->timestamp);
        $this->withSession([RequireAdminPassword::SESSION_KEY => time()]);

        $this->performAdminAction('POST', route('admin.queues.action'), [
            'action' => 'make_available', 'ids' => [(string) $reserved],
        ], ['admin.show_logs'])->assertSessionHas('success');

        $this->assertNotNull(QueueJob::findOrFail($reserved)->reserved_at);
    }

    public function test_dashboard_queue_details_require_logs_permission(): void
    {
        Setting::updateSettings([ScheduledTasksHealthService::HEARTBEAT_SETTING => now()], null, false);
        QueueWorkerHeartbeat::create([
            'connection' => 'database', 'queue' => 'default', 'processed_at' => now(),
        ]);
        $this->createJob(now()->subMinutes(16)->timestamp, now()->subMinutes(16)->timestamp);

        $this->performAdminAction('GET', route('admin.dashboard'), [], ['admin.show_logs'])
            ->assertOk()->assertSee(__('admin.dashboard.queue_jobs_blocked', ['count' => 1]));
        $this->performAdminAction('GET', route('admin.dashboard'), [], ['admin.manage_settings'])
            ->assertOk()->assertDontSee(__('admin.dashboard.queue_jobs_blocked', ['count' => 1]));
    }

    private function createJob(int $createdAt, int $availableAt, ?int $reservedAt = null): int
    {
        return QueueJob::create([
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'Tests\\FakeJob']),
            'attempts' => 0,
            'reserved_at' => $reservedAt,
            'available_at' => $availableAt,
            'created_at' => $createdAt,
        ])->id;
    }

    private function createFailedJob(string $name): string
    {
        $uuid = (string) \Illuminate\Support\Str::uuid();
        FailedQueueJob::create([
            'uuid' => $uuid,
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['uuid' => $uuid, 'displayName' => $name, 'job' => 'Illuminate\\Queue\\CallQueuedHandler', 'data' => []]),
            'exception' => 'Failure',
            'failed_at' => now(),
        ]);

        return $uuid;
    }
}

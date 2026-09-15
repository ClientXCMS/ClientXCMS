<?php

namespace Tests\Feature\Console;

use App\Listeners\Core\RecordScheduledTaskFailed;
use App\Listeners\Core\RecordScheduledTaskFinished;
use App\Models\Admin\Setting;
use App\Models\ScheduledTaskRun;
use App\Services\Core\ScheduledTasksHealthService;
use Carbon\Carbon;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ScheduledTaskMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_successful_task_is_recorded_with_its_runtime(): void
    {
        $task = $this->task('services:renewals', 0);

        app(RecordScheduledTaskFinished::class)->handle(new ScheduledTaskFinished($task, 1.25));

        $this->assertDatabaseHas('scheduled_task_runs', [
            'task_name' => 'services:renewals',
            'status' => ScheduledTaskRun::STATUS_SUCCESS,
            'runtime' => 1.25,
            'exit_code' => 0,
        ]);
    }

    public function test_non_zero_exit_is_only_recorded_as_a_failure(): void
    {
        $task = $this->task('services:renewals', 1);

        app(RecordScheduledTaskFinished::class)->handle(new ScheduledTaskFinished($task, 0.5));
        app(RecordScheduledTaskFailed::class)->handle(new ScheduledTaskFailed(
            $task,
            new RuntimeException("Failure\nwith <script>alert(1)</script> details")
        ));

        $this->assertDatabaseCount('scheduled_task_runs', 1);
        $this->assertDatabaseHas('scheduled_task_runs', [
            'task_name' => 'services:renewals',
            'status' => ScheduledTaskRun::STATUS_FAILED,
            'error_message' => 'Failure with alert(1) details',
            'exception_class' => RuntimeException::class,
            'exit_code' => 1,
        ]);
    }

    public function test_a_later_success_resolves_the_active_failure_without_deleting_history(): void
    {
        ScheduledTaskRun::create($this->runData('failed', now()->subMinute()));
        ScheduledTaskRun::create($this->runData('success', now()));

        $this->assertCount(0, app(ScheduledTasksHealthService::class)->activeFailures());
        $this->assertDatabaseCount('scheduled_task_runs', 2);
    }

    public function test_heartbeat_is_stale_only_after_five_minutes(): void
    {
        Carbon::setTestNow('2026-08-25 12:00:00');
        $service = app(ScheduledTasksHealthService::class);

        $this->assertTrue($service->heartbeatIsStale());

        Setting::updateSettings([ScheduledTasksHealthService::HEARTBEAT_SETTING => now()->subMinutes(5)], null, false);
        $this->assertFalse($service->heartbeatIsStale());

        Setting::updateSettings([ScheduledTasksHealthService::HEARTBEAT_SETTING => now()->subMinutes(5)->subSecond()], null, false);
        $this->assertTrue($service->heartbeatIsStale());
    }

    public function test_history_older_than_thirty_days_is_pruned(): void
    {
        ScheduledTaskRun::create($this->runData('success', now()->subDays(31)));
        ScheduledTaskRun::create($this->runData('success', now()->subDays(30)));

        $this->assertSame(1, app(ScheduledTasksHealthService::class)->pruneHistory());
        $this->assertDatabaseCount('scheduled_task_runs', 1);
    }

    private function task(string $name, int $exitCode): Event
    {
        $task = Mockery::mock(Event::class);
        $task->description = $name;
        $task->exitCode = $exitCode;

        return $task;
    }

    private function runData(string $status, Carbon $executedAt): array
    {
        return [
            'task_name' => 'services:renewals',
            'status' => $status,
            'executed_at' => $executedAt,
        ];
    }
}

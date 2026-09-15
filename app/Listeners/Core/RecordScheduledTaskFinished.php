<?php

namespace App\Listeners\Core;

use App\Models\ScheduledTaskRun;
use Illuminate\Console\Events\ScheduledTaskFinished;

class RecordScheduledTaskFinished
{
    public function handle(ScheduledTaskFinished $event): void
    {
        // Laravel dispatches Finished before Failed for a non-zero exit code.
        if ($event->task->exitCode !== null && $event->task->exitCode !== 0) {
            return;
        }

        ScheduledTaskRun::create([
            'task_name' => $this->taskName($event->task),
            'status' => ScheduledTaskRun::STATUS_SUCCESS,
            'executed_at' => now(),
            'runtime' => $event->runtime,
            'exit_code' => $event->task->exitCode,
        ]);
    }

    private function taskName(object $task): string
    {
        return $task->description ?: $task->getSummaryForDisplay();
    }
}

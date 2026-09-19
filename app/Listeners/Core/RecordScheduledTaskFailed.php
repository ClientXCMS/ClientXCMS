<?php

namespace App\Listeners\Core;

use App\Models\ScheduledTaskRun;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Support\Str;

class RecordScheduledTaskFailed
{
    public function handle(ScheduledTaskFailed $event): void
    {
        ScheduledTaskRun::create([
            'task_name' => $event->task->description ?: $event->task->getSummaryForDisplay(),
            'status' => ScheduledTaskRun::STATUS_FAILED,
            'executed_at' => now(),
            'error_message' => Str::limit($this->sanitize($event->exception->getMessage()), 1000, '…'),
            'exception_class' => $event->exception::class,
            'exit_code' => $event->task->exitCode,
        ]);
    }

    private function sanitize(string $message): string
    {
        return trim((string) preg_replace('/\s+/', ' ', strip_tags($message)));
    }
}

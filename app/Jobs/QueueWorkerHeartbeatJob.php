<?php

namespace App\Jobs;

use App\Models\QueueWorkerHeartbeat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class QueueWorkerHeartbeatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $token) {}

    public function handle(): void
    {
        QueueWorkerHeartbeat::query()
            ->where('connection', $this->connection ?? config('queue-monitor.connection'))
            ->where('queue', $this->queue ?? config('queue-monitor.queue'))
            ->where('token', $this->token)
            ->update(['processed_at' => now()]);
    }
}

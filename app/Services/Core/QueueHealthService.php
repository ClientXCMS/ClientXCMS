<?php

namespace App\Services\Core;

use App\Jobs\QueueWorkerHeartbeatJob;
use App\Models\FailedQueueJob;
use App\Models\QueueJob;
use App\Models\QueueWorkerHeartbeat;
use Illuminate\Support\Str;

class QueueHealthService
{
    public function connection(): string
    {
        return (string) config('queue-monitor.connection', config('queue.default'));
    }

    public function queue(): string
    {
        return (string) config('queue-monitor.queue', 'default');
    }

    public function isSynchronous(): bool
    {
        return config("queue.connections.{$this->connection()}.driver") === 'sync';
    }

    public function isManageable(): bool
    {
        return config("queue.connections.{$this->connection()}.driver") === 'database';
    }

    public function dispatchHeartbeat(): void
    {
        if (! $this->isManageable()) {
            return;
        }

        $token = (string) Str::uuid();
        QueueWorkerHeartbeat::query()->updateOrCreate(
            ['connection' => $this->connection(), 'queue' => $this->queue()],
            ['token' => $token, 'dispatched_at' => now()]
        );

        QueueWorkerHeartbeatJob::dispatch($token)
            ->onConnection($this->connection())
            ->onQueue($this->queue());
    }

    public function heartbeat(): ?QueueWorkerHeartbeat
    {
        return QueueWorkerHeartbeat::query()
            ->where('connection', $this->connection())
            ->where('queue', $this->queue())
            ->first();
    }

    public function heartbeatIsStale(): bool
    {
        if (! $this->isManageable()) {
            return false;
        }

        $processedAt = $this->heartbeat()?->processed_at;

        return $processedAt === null || $processedAt->lt(now()->subMinutes(config('queue-monitor.heartbeat_after_minutes', 5)));
    }

    public function waitingQuery()
    {
        return QueueJob::query()->where('queue', $this->queue())->whereNull('reserved_at')->where('available_at', '<=', now()->timestamp);
    }

    public function delayedQuery()
    {
        return QueueJob::query()->where('queue', $this->queue())->whereNull('reserved_at')->where('available_at', '>', now()->timestamp);
    }

    public function reservedQuery()
    {
        return QueueJob::query()->where('queue', $this->queue())->whereNotNull('reserved_at');
    }

    public function blockedWaitingQuery()
    {
        return $this->waitingQuery()->where('created_at', '<', now()->subMinutes(config('queue-monitor.waiting_after_minutes', 15))->timestamp);
    }

    public function staleReservedQuery()
    {
        $retryAfter = (int) config("queue.connections.{$this->connection()}.retry_after", 90);

        return $this->reservedQuery()->where('reserved_at', '<', now()->subSeconds($retryAfter)->timestamp);
    }

    public function failedQuery()
    {
        return FailedQueueJob::query()->where('connection', $this->connection())->where('queue', $this->queue());
    }

    public function summary(): array
    {
        if (! $this->isManageable()) {
            return ['manageable' => false, 'synchronous' => $this->isSynchronous()];
        }

        return [
            'manageable' => true,
            'synchronous' => false,
            'heartbeat' => $this->heartbeat(),
            'heartbeat_stale' => $this->heartbeatIsStale(),
            'waiting' => $this->waitingQuery()->count(),
            'blocked' => $this->blockedWaitingQuery()->count(),
            'delayed' => $this->delayedQuery()->count(),
            'reserved' => $this->reservedQuery()->count(),
            'stale_reserved' => $this->staleReservedQuery()->count(),
            'failed' => $this->failedQuery()->count(),
        ];
    }

    public function displayName(string $payload): string
    {
        $decoded = json_decode($payload, true);

        return Str::limit((string) ($decoded['displayName'] ?? $decoded['job'] ?? __('admin.queues.unknown_job')), 150);
    }
}

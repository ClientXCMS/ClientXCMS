<?php

namespace App\Services\Core;

use App\Models\Admin\Setting;
use App\Models\ScheduledTaskRun;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ScheduledTasksHealthService
{
    public const HEARTBEAT_SETTING = 'app_cron_last_run';

    public const STALE_AFTER_MINUTES = 5;

    public function heartbeat(): ?Carbon
    {
        $value = Setting::query()->where('name', self::HEARTBEAT_SETTING)->value('value');

        return $value ? Carbon::parse($value) : null;
    }

    public function heartbeatIsStale(): bool
    {
        $heartbeat = $this->heartbeat();

        return $heartbeat === null || $heartbeat->lt(now()->subMinutes(self::STALE_AFTER_MINUTES));
    }

    /** @return Collection<int, ScheduledTaskRun> */
    public function activeFailures(): Collection
    {
        return ScheduledTaskRun::query()->activeFailures()->get();
    }

    public function pruneHistory(int $retentionDays = 30): int
    {
        return ScheduledTaskRun::query()
            ->where('executed_at', '<', now()->subDays($retentionDays))
            ->delete();
    }
}

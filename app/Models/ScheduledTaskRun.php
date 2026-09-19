<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ScheduledTaskRun extends Model
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'task_name',
        'status',
        'executed_at',
        'runtime',
        'error_message',
        'exception_class',
        'exit_code',
    ];

    protected function casts(): array
    {
        return [
            'executed_at' => 'datetime',
            'runtime' => 'float',
            'exit_code' => 'integer',
        ];
    }

    public function scopeActiveFailures(Builder $query): Builder
    {
        $latestRuns = self::query()
            ->selectRaw('MAX(id)')
            ->groupBy('task_name');

        return $query
            ->whereIn('id', $latestRuns)
            ->where('status', self::STATUS_FAILED)
            ->orderByDesc('executed_at');
    }
}

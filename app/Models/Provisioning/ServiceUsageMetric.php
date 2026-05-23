<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Models\Provisioning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * v2.16 — One sample of usage for a metered service.
 *
 * @property int $id
 * @property int $service_id
 * @property string $metric_key
 * @property float $value
 * @property \Illuminate\Support\Carbon $captured_at
 */
class ServiceUsageMetric extends Model
{
    use HasFactory;

    protected $table = 'service_usage_metrics';

    protected $fillable = [
        'service_id',
        'metric_key',
        'value',
        'captured_at',
    ];

    protected $casts = [
        'value' => 'float',
        'captured_at' => 'datetime',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}

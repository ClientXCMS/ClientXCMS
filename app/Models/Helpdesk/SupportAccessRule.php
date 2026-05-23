<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Models\Helpdesk;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * v2.16 — Configurable predicate evaluated when a customer tries to
 * open a ticket. See database/migrations/...create_support_access_rules
 * for the column-by-column rationale.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property bool $enabled
 * @property int $priority
 * @property int|null $scope_department_id
 * @property int|null $scope_product_id
 * @property array $predicates
 * @property string $message_key
 *
 * @method static Builder enabled()
 */
class SupportAccessRule extends Model
{
    use HasFactory;

    protected $table = 'support_access_rules';

    protected $fillable = [
        'name',
        'description',
        'enabled',
        'priority',
        'scope_department_id',
        'scope_product_id',
        'predicates',
        'message_key',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'predicates' => 'array',
        'priority' => 'integer',
        'scope_department_id' => 'integer',
        'scope_product_id' => 'integer',
    ];

    protected $attributes = [
        'enabled' => true,
        'priority' => 100,
        'predicates' => '{}',
        'message_key' => 'v216::helpdesk.access.default_denied',
    ];

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    public function department()
    {
        return $this->belongsTo(SupportDepartment::class, 'scope_department_id');
    }

    public function getRequiredServiceStatusesAttribute(): array
    {
        return (array) ($this->predicates['require_service_status'] ?? []);
    }

    public function getRequiredOptionUuidAttribute(): ?string
    {
        $value = $this->predicates['require_option_uuid'] ?? null;
        return $value !== null && $value !== '' ? (string) $value : null;
    }

    public function getRequiresActiveServiceAttribute(): bool
    {
        return (bool) ($this->predicates['require_active_service'] ?? false);
    }

    public function getBlockedPrioritiesAttribute(): array
    {
        return (array) ($this->predicates['block_priorities'] ?? []);
    }

    public function getRequiredRelatedTypeAttribute(): ?string
    {
        $value = $this->predicates['require_related_type'] ?? null;
        return $value !== null && $value !== '' ? (string) $value : null;
    }
}

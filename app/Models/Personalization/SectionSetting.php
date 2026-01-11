<?php
/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2026
 */

namespace App\Models\Personalization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $section_id
 * @property string $key
 * @property string|null $value
 * @property string|null $locale
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Personalization\Section $section
 */
class SectionSetting extends Model
{
    protected $table = 'section_settings';

    protected $fillable = [
        'section_id',
        'key',
        'value',
        'locale',
    ];

    protected $casts = [
        'section_id' => 'integer',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get typed value based on field definition type
     */
    public function getTypedValue(string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'number' => (int) $this->value,
            'json', 'repeater' => json_decode($this->value, true) ?? [],
            default => $this->value,
        };
    }
}

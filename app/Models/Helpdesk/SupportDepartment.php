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
 * Year: 2025
 */

namespace App\Models\Helpdesk;

use App\Models\Traits\Loggable;
use App\Models\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @OA\Schema (
 *     schema="SupportDepartment",
 *     title="Support Department",
 *     description="A support department that handles support tickets and groups staff subscribers.",
 *     required={"name", "description"},
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Technical Support"),
 *     @OA\Property(property="description", type="string", example="Handles all technical issues related to services."),
 *     @OA\Property(property="icon", type="string", example="bi bi-question-circle", description="Bootstrap icon class"),
 *     @OA\Property(
 *         property="staff_subscribers",
 *         type="array",
 *         description="List of admin IDs subscribed to this department",
 *
 *         @OA\Items(type="integer"),
 *         example={2, 5, 9}
 *     ),
 *
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-03-01T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-04-01T15:30:00Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(
 *         property="tickets",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/SupportTicket"),
 *         description="List of tickets belonging to this department"
 *     )
 * )
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $icon
 * @property array|null $staff_subscribers
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Helpdesk\SupportTicket> $tickets
 * @property-read int|null $tickets_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Personalization\Translation> $translations
 * @property-read int|null $translations_count
 *
 * @method static \Database\Factories\Helpdesk\DepartmentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportDepartment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportDepartment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportDepartment onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportDepartment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportDepartment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportDepartment whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportDepartment whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportDepartment whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportDepartment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportDepartment whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportDepartment whereStaffSubscribers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportDepartment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportDepartment withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportDepartment withoutTrashed()
 *
 * @mixin \Eloquent
 */
class SupportDepartment extends Model
{
    use HasFactory, Loggable, softDeletes, Translatable;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'staff_subscribers',
    ];

    protected $casts = [
        'staff_subscribers' => 'array',
    ];

    protected $attributes = [
        'icon' => 'bi bi-question-circle',
    ];

    protected $translatableKeys = [
        'name' => 'text',
        'description' => 'textarea',
    ];

    public static function boot()
    {
        parent::boot();
        static::deleting(function ($department) {
            $department->tickets()->delete();
        });
        self::observe(\App\Observers\SupportDepartmentObserver::class);
    }

    public function tickets()
    {
        return $this->hasMany(SupportTicket::class, 'department_id');
    }

    protected static function newFactory()
    {
        return \Database\Factories\Helpdesk\DepartmentFactory::new();
    }
}

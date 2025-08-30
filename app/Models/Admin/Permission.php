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


namespace App\Models\Admin;

use App\Models\Helpdesk\SupportDepartment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @OA\Schema (
 *     schema="Permission",
 *     title="Permission",
 *     description="System permission attached to an admin role",
 *     required={"name", "label"},
 * 
 *     @OA\Property(property="id", type="integer", example=10),
 *     @OA\Property(property="name", type="string", example="admin.manage_settings", description="Internal permission key"),
 *     @OA\Property(property="label", type="string", example="Manage settings", description="Human-readable permission label (translated)"),
 *     @OA\Property(property="group", type="string", nullable=true, example="Settings", description="Optional group for permission grouping"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-04-01T12:30:00Z")
 * )
 * @property int $id
 * @property string $name
 * @property string $label
 * @property string|null $group
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Permission extends Model
{
    use HasFactory;

    const MANAGE_EXTENSIONS = 'admin.manage_extensions';

    const MANAGE_PERSONALIZATION = 'admin.manage_personalization';

    const MANAGE_SETTINGS = 'admin.manage_settings';

    const ALLOWED = 'admin.allowed';

    protected $fillable = [
        'name',
        'label',
        'group',
    ];

    public function translate()
    {
        if ($this->label == 'permissions.manage_tickets_department') {
            $department = explode('.', $this->name)[2];
            $department = SupportDepartment::find($department);
            if ($department == null) {
                return __($this->label);
            }

            return __($this->label, ['name' => $department->trans('name')]);
        }

        return __($this->label);
    }
}

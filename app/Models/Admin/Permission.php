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

    // Core permissions
    const ALLOWED = 'admin.allowed';
    const MANAGE_SETTINGS = 'admin.manage_settings';

    // Admin permissions
    const MANAGE_LICENSE = 'admin.manage_license';
    const MANAGE_GATEWAYS = 'admin.manage_gateways';
    const MANAGE_ROLES = 'admin.manage_roles';
    const MANAGE_STAFF = 'admin.manage_staff';
    const MANAGE_STAFF_PROFILE = 'admin.manage_staff_profile';

    // Billing permissions
    const MANAGE_INVOICES = 'admin.manage_invoices';
    const EXPORT_INVOICES = 'admin.export_invoices';
    const SHOW_INVOICES = 'admin.show_invoices';
    const CREATE_INVOICES = 'admin.create_invoices';
    const SHOW_PAYMENT_METHODS = 'admin.show_payment_methods';

    // Customer permissions
    const SHOW_CUSTOMERS = 'admin.show_customers';
    const MANAGE_CUSTOMERS = 'admin.manage_customers';
    const AUTOLOGIN_CUSTOMER = 'admin.autologin_customer';
    const SHOW_EMAILS = 'admin.show_emails';
    const SEND_EMAILS = 'admin.send_emails';
    const MANAGE_EMAILS = 'admin.manage_emails';
    const DASHBOARD_LAST_LOGIN = 'admin.dashboard_last_login';

    // Helpdesk permissions
    const SHOW_HELPDESK_ANALYTICS = 'admin.show_helpdesk_analytics';
    const CLOSE_TICKETS = 'admin.close_tickets';
    const MANAGE_TICKETS = 'admin.manage_tickets';
    const MANAGE_DEPARTMENTS = 'admin.manage_departments';

    // Service permissions
    const SHOW_SERVICES = 'admin.show_services';
    const DELIVER_SERVICES = 'admin.deliver_services';
    const MANAGE_SERVICES = 'admin.manage_services';

    // Store permissions
    const EARN_PAGE = 'admin.earn_page';
    const MANAGE_PRODUCTS = 'admin.manage_products';
    const MANAGE_CONFIGOPTIONS = 'admin.manage_configoptions';
    const MANAGE_GROUPS = 'admin.manage_groups';
    const MANAGE_COUPONS = 'admin.manage_coupons';

    // Technical permissions
    const MANAGE_DATABASE = 'admin.manage_database';
    const MANAGE_SUBDOMAINS_HOSTS = 'admin.manage_subdomains_hosts';
    const SHOW_LOGS = 'admin.show_logs';
    const MANAGE_SERVERS = 'admin.manage_servers';
    const MANAGE_PERSONALIZATION = 'admin.manage_personalization';
    const MANAGE_EXTENSIONS = 'admin.manage_extensions';
    const MANAGE_API_KEYS = 'admin.manage_api_keys';
    const MANAGE_UPDATE = 'admin.manage_update';

    // Metadata permissions
    const MANAGE_METADATA = 'admin.manage_metadata';
    const SHOW_METADATA = 'admin.show_metadata';

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

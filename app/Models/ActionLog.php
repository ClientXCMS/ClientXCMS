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


namespace App\Models;

use App\Models\Account\Customer;
use App\Models\Admin\Admin;
use App\Models\Admin\Setting;
use Arr;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property int|null $customer_id
 * @property int|null $staff_id
 * @property string $action
 * @property string|null $model
 * @property string|null $model_id
 * @property array|null $payload
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Customer|null $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActionLogEntries> $entries
 * @property-read int|null $entries_count
 * @property-read Admin|null $staff
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionLog whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionLog whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionLog whereModelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionLog wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionLog whereStaffId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ActionLog extends Model
{
    use HasFactory;

    const SETTINGS_UPDATED = 'settings_updated';

    const NEW_LOGIN = 'new_login';

    const RESOURCE_CREATED = 'resource_created';

    const RESOURCE_UPDATED = 'resource_updated';

    const RESOURCE_DELETED = 'resource_deleted';

    const RESOURCE_CLONED = 'resource_cloned';

    const CHECKOUT_COMPLETED = 'checkout_completed';

    const SERVICE_DELIVERED = 'service_delivered';

    const SERVICE_CANCELLED = 'service_cancelled';

    const SERVICE_UNCANCELLED = 'service_uncancelled';

    const SERVICE_SUSPENDED = 'service_suspended';

    const SERVICE_UNSUSPENDED = 'service_unsuspended';

    const INVOICE_PAID = 'invoice_paid';

    const SERVICE_RENEWED = 'service_renewed';

    const OTHER = 'other';

    const SERVICE_EXPIRED = 'service_expired';

    const EXTENSION_ENABLED = 'extension_enabled';

    const EXTENSION_DISABLED = 'extension_disabled';

    const EXTENSION_INSTALLED = 'extension_installed';

    const EXTENSION_UPDATED = 'extension_updated';

    const THEME_CHANGED = 'theme_changed';

    const BALANCE_CHANGED = 'balance_changed';

    const ACCOUNT_VERIFIED = 'account_verified';

    const PASSWORD_RESET = 'password_reset';

    const TWO_FACTOR_ENABLED = 'two_factor_enabled';

    const TWO_FACTOR_DISABLED = 'two_factor_disabled';

    const TWO_FACTOR_RECOVERY_CODES_GENERATED = 'two_factor_recovery_codes_generated';

    const FAILED_LOGIN = 'failed_login';

    const ALL_ACTIONS = [
        self::SETTINGS_UPDATED,
        self::RESOURCE_CREATED,
        self::RESOURCE_UPDATED,
        self::RESOURCE_DELETED,
        self::RESOURCE_CLONED,
        self::CHECKOUT_COMPLETED,
        self::SERVICE_DELIVERED,
        self::SERVICE_CANCELLED,
        self::SERVICE_UNCANCELLED,
        self::SERVICE_SUSPENDED,
        self::SERVICE_UNSUSPENDED,
        self::INVOICE_PAID,
        self::SERVICE_RENEWED,
        self::SERVICE_EXPIRED,
        self::EXTENSION_ENABLED,
        self::EXTENSION_DISABLED,
        self::EXTENSION_INSTALLED,
        self::EXTENSION_UPDATED,
        self::THEME_CHANGED,
        self::NEW_LOGIN,
        self::BALANCE_CHANGED,
        self::ACCOUNT_VERIFIED,
        self::PASSWORD_RESET,
        self::TWO_FACTOR_ENABLED,
        self::TWO_FACTOR_DISABLED,
        self::TWO_FACTOR_RECOVERY_CODES_GENERATED,
        self::FAILED_LOGIN,
    ];

    protected static array $ignoreKeys = [];

    protected $fillable = [
        'customer_id',
        'staff_id',
        'action',
        'model',
        'model_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function staff()
    {
        return $this->belongsTo(Admin::class)->withTrashed();
    }

    public function getIcon()
    {
        switch ($this->action) {
            case self::SETTINGS_UPDATED:
            case self::RESOURCE_UPDATED:
                return 'bi bi-gear';
            case self::RESOURCE_CREATED:
                return 'bi bi-plus';
            case self::RESOURCE_DELETED:
                return 'bi bi-trash';
            case self::RESOURCE_CLONED:
                return 'bi bi-clipboard';
            case self::CHECKOUT_COMPLETED:
                return 'bi bi-cart-check';
            case self::ACCOUNT_VERIFIED:
            case self::SERVICE_DELIVERED:
                return 'bi bi-check-circle';
            case self::SERVICE_CANCELLED:
                return 'bi bi-x-circle';
            case self::SERVICE_RENEWED:
            case self::SERVICE_UNCANCELLED:
            case self::EXTENSION_UPDATED:
                return 'bi bi-arrow-repeat';
            case self::SERVICE_SUSPENDED:
                return 'bi bi-pause-circle';
            case self::SERVICE_UNSUSPENDED:
                return 'bi bi-play-circle';
            case self::INVOICE_PAID:
                return 'bi bi-cash-coin';
            case self::SERVICE_EXPIRED:
                return 'bi bi-clock';
            case self::EXTENSION_ENABLED:
                return 'bi bi-box-arrow-in-up';
            case self::EXTENSION_DISABLED:
                return 'bi bi-box-arrow-down';
            case self::EXTENSION_INSTALLED:
                return 'bi bi-box-arrow-in-down';
            case self::BALANCE_CHANGED:
                return 'bi bi-currency-dollar';
            case self::THEME_CHANGED:
                return 'bi bi-palette';
            case self::NEW_LOGIN:
                return 'bi bi-door-open';
            case self::PASSWORD_RESET:
                return 'bi bi-key';
            case self::TWO_FACTOR_ENABLED:
                return 'bi bi-shield-lock';
            case self::TWO_FACTOR_DISABLED:
            case self::FAILED_LOGIN:
                return 'bi bi-shield-slash';
            case self::TWO_FACTOR_RECOVERY_CODES_GENERATED:
                return 'bi bi-shield-check';
            default:
                return 'bi bi-question-circle';
        }
    }

    public static function log($action, $model, $modelId, $staffId = null, $customerId = null, $payload = [], $old = [], $new = [])
    {
        if (collect($old)->keys()->filter(fn ($key) => in_array($key, self::$ignoreKeys ?? []))->isNotEmpty()) {
            return null;
        }
        $log = self::create([
            'customer_id' => $customerId,
            'staff_id' => $staffId,
            'action' => $action,
            'model' => $model,
            'model_id' => $modelId,
            'payload' => $payload,
        ]);

        if ($old && $new) {
            $log->createEntries($old, $new);
        }

        return $log;
    }

    public function entries()
    {
        return $this->hasMany(ActionLogEntries::class);
    }

    public function getFormattedName()
    {
        $parameters = $this->getParameters();
        $action = __("actionslog.actions.{$this->action}", $parameters);

        return $action;
    }

    private function getParameters()
    {
        if (str_starts_with($this->action, 'resource_')) {
            $modelClass = explode('\\', $this->model);
            $model = strtolower(end($modelClass));

            return [
                'model' => $model.' #'.$this->model_id,
            ];
        }
        if (str_starts_with($this->action, 'service_') || $this->action === self::INVOICE_PAID) {
            return [
                'id' => $this->model_id,
            ];
        }
        if (str_starts_with($this->action, 'extension_')) {
            return [
                'extension' => $this->model_id,
            ];
        }

        return $this->payload;

    }

    public function userlink()
    {
        if ($this->customer_id) {
            return route('admin.customers.show', $this->customer_id);
        }
        if ($this->staff_id) {
            return route('admin.staffs.show', $this->staff_id);
        }

        return '#';
    }

    public function username()
    {
        if ($this->customer_id) {
            if ($this->customer == null) {
                return 'Deleted Customer';
            }

            return $this->customer->excerptFullName();
        }
        if ($this->staff_id) {
            if ($this->staff == null) {
                return 'Deleted Staff';
            }

            return $this->staff->excerptFullName();
        }

        return 'System';
    }

    public function createEntries(array $old, array $new): void
    {
        foreach ($old as $attribute => $oldValue) {
            $newValue = Arr::get($new, $attribute);
            if ($oldValue != $newValue && ! in_array($attribute, self::$ignoreLogAttributes ?? [])) {
                if (in_array($attribute, (new Setting)->encrypt)) {
                    $oldValue = 'Encrypted';
                    $newValue = 'Encrypted';
                }
                $this->entries()->create([
                    'attribute' => $attribute,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                ]);
            }
        }
    }
}

<?php

namespace App\Models\Account;

use App\Models\Provisioning\Service;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAccountAccess extends Model
{
    use HasFactory;

    public const SERVICE_PERMISSIONS = [
        'service.show',
        'service.name',

        'service.renew',
        'service.upgrade',
        'service.options',
        'service.billing',
        'service.cancel',
    ];

    public const INVOICE_PERMISSIONS = [
        'invoice.show',
        'invoice.pay',
        'invoice.download',
        'invoice.balance',
    ];

    public const SERVICE_PERMISSIONS_REQUIRING_INVOICES = [
        'service.renew',
        'service.upgrade',
    ];

    public const PERMISSIONS = [
        'service.show',
        'service.renew',
        'service.upgrade',
        'service.options',
        'service.billing',
        'service.name',
        'service.cancel',
        'invoice.show',
        'invoice.pay',
        'invoice.download',
        'invoice.balance',
    ];

    protected $fillable = [
        'owner_customer_id',
        'sub_customer_id',
        'created_by_customer_id',
        'permissions',
        'all_services',
    ];

    protected $casts = [
        'permissions' => 'array',
        'all_services' => 'boolean',
    ];

    public function owner()
    {
        return $this->belongsTo(Customer::class, 'owner_customer_id')->withTrashed();
    }

    public function subCustomer()
    {
        return $this->belongsTo(Customer::class, 'sub_customer_id')->withTrashed();
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'customer_account_access_service')->withTimestamps();
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? [], true);
    }

    public function allowsService(Service $service, string $permission): bool
    {
        if ($service->customer_id !== $this->owner_customer_id || ! $this->hasPermission($permission)) {
            return false;
        }

        return $this->all_services || $this->services()->whereKey($service->id)->exists();
    }
}

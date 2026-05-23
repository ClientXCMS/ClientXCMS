<?php

namespace App\Models\Account;

use App\Models\ActionLog;
use App\Models\Provisioning\Service;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CustomerAccountInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_customer_id',
        'email',
        'token',
        'permissions',
        'all_services',
        'expires_at',
        'accepted_at',
        'revoked_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'all_services' => 'boolean',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function (CustomerAccountInvitation $invitation) {
            if (empty($invitation->token)) {
                $invitation->token = Str::random(64);
            }
            if ($invitation->expires_at === null) {
                $invitation->expires_at = now()->addDays(14);
            }
            $invitation->email = strtolower($invitation->email);
        });
    }

    public function owner()
    {
        return $this->belongsTo(Customer::class, 'owner_customer_id')->withTrashed();
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'customer_account_invitation_service')->withTimestamps();
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null
            && $this->revoked_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function accept(Customer $customer): CustomerAccountAccess
    {
        abort_if(strtolower($customer->email) !== strtolower($this->email), 403);
        abort_if(! $this->isPending(), 404);

        $access = CustomerAccountAccess::updateOrCreate([
            'owner_customer_id' => $this->owner_customer_id,
            'sub_customer_id' => $customer->id,
        ], [
            'created_by_customer_id' => $this->owner_customer_id,
            'permissions' => $this->permissions,
            'all_services' => $this->all_services,
        ]);

        if (! $this->all_services) {
            $access->services()->sync($this->services()->pluck('services.id')->all());
        } else {
            $access->services()->detach();
        }

        $this->forceFill(['accepted_at' => now()])->save();

        ActionLog::log(ActionLog::OTHER, self::class, $this->id, null, $this->owner_customer_id, [
            'message' => 'customer_account_invitation_accepted',
            'email' => $this->email,
        ]);

        return $access;
    }
}

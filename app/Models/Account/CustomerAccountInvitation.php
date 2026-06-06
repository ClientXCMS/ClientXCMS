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

    // Transient plain token for mail rendering, never persisted (DB has sha256 only).
    public ?string $plain_text_token = null;

    public static function boot()
    {
        parent::boot();

        static::creating(function (CustomerAccountInvitation $invitation) {
            if (empty($invitation->getAttribute('token'))) {
                $invitation->setFreshToken();
            }
            if ($invitation->expires_at === null) {
                $invitation->expires_at = now()->addDays(14);
            }
            $invitation->email = strtolower($invitation->email);
        });
    }

    // Rotate token: leak of the old URL stops working as soon as caller resends.
    public function setFreshToken(): string
    {
        $plain = Str::random(64);
        $this->plain_text_token = $plain;
        $this->setAttribute('token', hash('sha256', $plain));

        return $plain;
    }

    // Hashes the plain token before lookup; returns null on miss (caller 404s).
    public static function findByPlainToken(string $plainToken): ?self
    {
        return static::where('token', hash('sha256', $plainToken))->first();
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

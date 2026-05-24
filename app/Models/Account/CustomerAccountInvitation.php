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

    /**
     * Plain token. Set transiently when the row is created or rotated so
     * the mail layer can render the invitation URL. Never persisted - the
     * DB only sees its sha256 hash. After a fresh fetch this is null.
     */
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

    /**
     * Generates a new plain token, exposes it on the model instance, and
     * stores only its sha256. Called on create and on every resend so a
     * leaked URL stops working as soon as the user asks for a fresh one.
     */
    public function setFreshToken(): string
    {
        $plain = Str::random(64);
        $this->plain_text_token = $plain;
        $this->setAttribute('token', hash('sha256', $plain));

        return $plain;
    }

    /**
     * Looks up an invitation by its plain token. The plain value never
     * touches the DB - the lookup hashes it first and matches the stored
     * sha256. Returns null when no row matches (caller handles 404).
     */
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

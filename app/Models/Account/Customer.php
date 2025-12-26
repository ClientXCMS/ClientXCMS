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

namespace App\Models\Account;

use App\Casts\CustomRawPhoneNumberCast;
use App\Contracts\Notifications\HasNotifiableVariablesInterface;
use App\Contracts\Notifications\NotifiablePlaceholderInterface;
use App\Mail\Auth\ResetPasswordEmail;
use App\Mail\Auth\VerifyEmail;
use App\Models\ActionLog;
use App\Models\Billing\Invoice;
use App\Models\Helpdesk\SupportTicket;
use App\Models\Provisioning\Service;
use App\Models\Traits\CanBlocked;
use App\Models\Traits\CanUse2FA;
use App\Models\Traits\HasMetadata;
use App\Models\Traits\HasPaymentMethods;
use App\Models\Traits\Loggable;
use App\Observers\CustomerObserver;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\HasApiTokens;

/**
 * @OA\Schema (
 *      schema="Customer",
 *     title="Customer",
 *     description="Customer model"
 * )
 *
 * @property int $id
 * @property string $firstname
 * @property string $lastname
 * @property string $email
 * @property string $locale
 * @property $phone
 * @property string $address
 * @property string $support_id
 * @property string|null $address2
 * @property string $city
 * @property string $country
 * @property string $region
 * @property string $zipcode
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property float $balance
 * @property Carbon|null $last_login
 * @property string|null $last_ip
 * @property int $is_confirmed
 * @property string|null $confirmation_token
 * @property int $is_deleted
 * @property Carbon|null $deleted_at
 * @property string|null $totp_secret
 * @property int $dark_mode
 * @property string|null $notes
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Account\EmailMessage> $emails
 * @property-read int|null $emails_count
 * @property-read string $full_name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Metadata> $metadata
 * @property-read int|null $metadata_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SupportTicket> $tickets
 * @property-read int|null $tickets_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 *
 * @method static \Database\Factories\Core\CustomerFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereAddress2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereConfirmationToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereDarkMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereFirstname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereIsConfirmed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereIsDeleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereLastIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereLastLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereLastname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereRegion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereTotpSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereZipcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer withoutTrashed()
 *
 * @property string|null $company_name
 * @property string|null $billing_details
 * @property int $gdpr_compliment
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereBillingDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCompanyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereGdprCompliment($value)
 *
 * @mixin \Eloquent
 */
class Customer extends Authenticatable implements \Illuminate\Contracts\Auth\MustVerifyEmail, HasNotifiableVariablesInterface, NotifiablePlaceholderInterface
{
    use CanBlocked, CanUse2FA, HasApiTokens, HasFactory, HasMetadata, HasPaymentMethods, Loggable, MustVerifyEmail, Notifiable, softDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     *
     * @OA\Property(
     *     property="email",
     *     type="string",
     *     description="Customer email"
     * )
     * @OA\Property(
     *     property="password",
     *     type="string",
     *     description="Customer password"
     * )
     * @OA\Property(
     *     property="firstname",
     *     type="string",
     *     description="Customer firstname"
     * )
     * @OA\Property(
     *     property="lastname",
     *     type="string",
     *     description="Customer lastname"
     * )
     * @OA\Property(
     *     property="phone",
     *     type="string",
     *     description="Customer phone"
     * )
     * @OA\Property(
     *     property="address",
     *     type="string",
     *     description="Customer address"
     * )
     * @OA\Property(
     *     property="address2",
     *     type="string",
     *     description="Customer address line 2"
     * )
     * @OA\Property(
     *     property="city",
     *     type="string",
     *     description="Customer city"
     * )
     * @OA\Property(
     *     property="country",
     *     type="string",
     *     description="Customer country"
     * )
     * @OA\Property(
     *     property="locale",
     *     type="string",
     *     description="Customer locale"
     * )
     * @OA\Property(
     *     property="region",
     *     type="string",
     *     description="Customer region"
     * )
     * @OA\Property(
     *     property="zipcode",
     *     type="string",
     *     description="Customer zipcode"
     * )
     * @OA\Property(
     *     property="email_verified_at",
     *     type="string",
     *     format="date-time",
     *     description="Customer email verification timestamp"
     * )
     * @OA\Property(
     *     property="is_confirmed",
     *     type="boolean",
     *     description="Customer confirmation status"
     * )
     * @OA\Property(
     *     property="is_deleted",
     *     type="boolean",
     *     description="Customer deletion status"
     * )
     * @OA\Property(
     *     property="dark_mode",
     *     type="boolean",
     *     description="Customer theme mode"
     * )
     * @OA\Property(
     *     property="last_login",
     *     type="string",
     *     format="date-time",
     *     description="Last login timestamp"
     * )
     * @OA\Property(
     *     property="last_ip",
     *     type="string",
     *     description="Last login IP address"
     * )
     * @OA\Property(
     *     property="company_name",
     *     type="string",
     *     description="Customer company name"
     * )
     * @OA\Property(
     *     property="billing_details",
     *     type="string",
     *     description="Customer billing details"
     * * )
     * @OA\Property(
     *     property="gdpr_compliment",
     *     type="boolean",
     *     description="Indicates if the customer has given GDPR consent"
     * )
     */
    protected $fillable = [
        'email',
        'password',
        'firstname',
        'lastname',
        'email',
        'phone',
        'support_id',
        'address',
        'address2',
        'city',
        'country',
        'region',
        'zipcode',
        'email_verified_at',
        'is_confirmed',
        'is_deleted',
        'last_login',
        'dark_mode',
        'last_ip',
        'notes',
        'balance',
        'locale',
        'billing_details',
        'company_name',
        'gdpr_compliment',
        'security_question_id',
        'security_answer',
    ];

    protected $attributes = [
        'dark_mode' => false,
        'balance' => 0,
        'country' => 'FR',
        'locale' => 'fr_FR',
        'gdpr_compliment' => false,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'updated_at',
        'last_ip',
        'security_answer',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login' => 'datetime',
        'phone' => CustomRawPhoneNumberCast::class.':FR',
        'balance' => 'decimal:2',
    ];

    public static function boot()
    {
        parent::boot();
        static::observe(CustomerObserver::class);
    }

    public static function sumCustomers()
    {
        return Service::countCustomers();
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmail($this));
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordEmail($token));
    }

    public function markEmailAsVerified()
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
            'is_confirmed' => true,
        ])->save();
    }

    public function emails()
    {
        return $this->hasMany(EmailMessage::class, 'recipient_id');
    }

    public function services(bool $hidden = false)
    {
        if (! $hidden) {
            return $this->hasMany(Service::class, 'customer_id')->whereNot('status', Service::STATUS_HIDDEN);
        }

        return $this->hasMany(Service::class, 'customer_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }

    public function tickets()
    {
        return $this->hasMany(SupportTicket::class, 'customer_id');
    }

    public function customerNotes()
    {
        return $this->hasMany(CustomerNote::class, 'customer_id');
    }

    protected static function newFactory()
    {
        return \Database\Factories\Core\CustomerFactory::new();
    }

    public function getFullNameAttribute(): string
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    public function excerptFullName(int $length = 24): string
    {
        return \Str::limit($this->fullName, $length);
    }

    public function hasServicePermission(Service $service, string $permission)
    {
        return $service->customer_id == $this->id;
    }

    public function getConfirmationUrl()
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $this->getKey(),
                'hash' => sha1($this->getEmailForVerification()),
            ]
        );
    }

    public function supportRelatedItems()
    {
        return $this->invoices->merge($this->services)->mapWithKeys(function ($item) {
            return [$item->relatedType() . '-' . $item->relatedId() => $item->relatedName()];
        })->put('none', __('helpdesk.support.create.relatednone'));
    }

    public function initials()
    {
        return $this->firstname[0] . $this->lastname[0];
    }

    public function notify($instance)
    {
        try {
            app(Dispatcher::class)->send($this, $instance);
            \Cache::forget('notification_error');
        } catch (\Exception $e) {
            \Cache::put('notification_error', $e->getMessage() . ' | Date : ' . date('Y-m-d H:i:s'), 3600 * 24);
        }
    }

    public function addFund(float $amount, ?string $reason = null)
    {
        $old = $this->balance;
        $this->balance += $amount;
        $this->save();
        if ($reason !== null) {
            $reason = ' '.strtolower(__('global.for')).' '.$reason;
            if (auth('admin')->check()) {
                $adminId = auth('admin')->id();
            } else {
                $adminId = null;
            }
            ActionLog::log(ActionLog::BALANCE_CHANGED, self::class, $this->id, $adminId, $this->id, ['old' => formatted_price($old), 'new' => formatted_price($this->balance), 'reason' => $reason], ['balance' => $old], ['balance' => $this->balance]);
        }
    }

    public function getBadgeColor()
    {
        if ($this->isBanned()) {
            return 'red';
        }
        if ($this->isSuspended() || ! $this->is_confirmed) {
            return 'yellow';
        }

        return 'green';
    }

    public function getNotificationVariables(): array
    {
        return [
            '%customer_name%' => $this->fullname,
            '%customer_email%' => $this->email,
            '%customer_phone%' => $this->phone,
            '%customer_address%' => $this->address,
            '%customer_address2%' => $this->address2,
            '%customer_city%' => $this->city,
            '%customer_country%' => $this->country,
            '%customer_region%' => $this->region,
            '%customer_zipcode%' => $this->zipcode,
            '%customer_locale%' => $this->locale,
            '%customer_firstname%' => $this->firstname,
            '%customer_lastname%' => $this->lastname,
        ];
    }

    public static function getNotificationContextVariables(): array
    {
        return [
            '%customer_name%',
            '%customer_email%',
            '%customer_phone%',
            '%customer_address%',
            '%customer_address2%',
            '%customer_city%',
            '%customer_country%',
            '%customer_region%',
            '%customer_zipcode%',
            '%customer_locale%',
            '%customer_firstname%',
            '%customer_lastname%',
        ];
    }

    public function getPendingInvoices()
    {
        return $this->invoices()->where('status', 'pending')->get();
    }

    public function getPendingInvoicesArray()
    {
        return $this->getPendingInvoices()->mapWithKeys(function (Invoice $invoice) {
            return [
                $invoice->id => __('global.invoice') . ' - ' . $invoice->invoice_number,
            ];
        });
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function generateBillingAddress()
    {
        return [
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'company_name' => $this->company_name,
            'address' => $this->address,
            'address2' => $this->address2,
            'city' => $this->city,
            'country' => $this->country,
            'region' => $this->region,
            'zipcode' => $this->zipcode,
            'phone' => $this->phone,
            'email' => $this->email,
            'billing_details' => $this->billing_details,
        ];
    }

    /**
     * Check if customer is trusted based on account age and service count.
     * Used for review auto-publication (Option 4).
     */
    public function isTrustedForReviews(): bool
    {
        // If trusted client feature is disabled, always return false
        if (! setting('reviews_trusted_client_enabled', false)) {
            return false;
        }

        // Check account age
        $minMonths = (int) setting('reviews_trusted_client_min_months', 6);
        $accountAgeInMonths = $this->created_at->diffInMonths(now());

        if ($accountAgeInMonths < $minMonths) {
            return false;
        }

        // Check active service count
        $minServices = (int) setting('reviews_trusted_client_min_services', 3);
        $activeServicesCount = $this->services()
            ->where('status', 'active')
            ->count();

        if ($activeServicesCount < $minServices) {
            return false;
        }

        return true;
    }

    /**
     * Get the security question associated with the customer.
     */
    public function securityQuestion()
    {
        return $this->belongsTo(\App\Models\Admin\SecurityQuestion::class, 'security_question_id');
    }

    /**
     * Check if the customer has a security question set.
     */
    public function hasSecurityQuestion(): bool
    {
        return $this->security_question_id !== null && $this->security_answer !== null;
    }

    /**
     * Verify the security answer (case insensitive).
     */
    public function verifySecurityAnswer(string $answer): bool
    {
        if (! $this->hasSecurityQuestion()) {
            return true;
        }

        return strtolower(trim($answer)) === strtolower(trim($this->security_answer));
    }

    /**
     * Set the security question and answer.
     */
    public function setSecurityQuestion(int $questionId, string $answer): void
    {
        $this->update([
            'security_question_id' => $questionId,
            'security_answer' => strtolower(trim($answer)),
        ]);
    }

    /**
     * Reset the security question.
     */
    public function resetSecurityQuestion(): void
    {
        $this->update([
            'security_question_id' => null,
            'security_answer' => null,
        ]);
    }
}

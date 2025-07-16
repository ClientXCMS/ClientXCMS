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
 * Year: 2025
 */
namespace App\Models\Admin;

use App\Contracts\Notifications\NotifiablePlaceholderInterface;
use App\Mail\Auth\ResetPasswordEmail;
use App\Mail\MailTested;
use App\Models\Account\Customer;
use App\Models\Traits\CanUse2FA;
use App\Models\Traits\HasMetadata;
use App\Models\Traits\Loggable;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * 
 *
 * @OA\Schema (
 *     schema="Admin",
 *     title="Admin",
 *     description="Administrator user model",
 *     required={"email", "password", "username", "firstname", "lastname"},
 * 
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="email", type="string", format="email", example="admin@example.com"),
 *     @OA\Property(property="username", type="string", example="adminmaster"),
 *     @OA\Property(property="firstname", type="string", example="John"),
 *     @OA\Property(property="lastname", type="string", example="Doe"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true, example="2024-03-15T10:45:00Z"),
 *     @OA\Property(property="last_login", type="string", format="date-time", nullable=true, example="2024-04-12T09:00:00Z"),
 *     @OA\Property(property="last_login_ip", type="string", format="ipv4", nullable=true, example="192.168.1.10"),
 *     @OA\Property(property="signature", type="string", nullable=true, example="Best regards,\nThe Admin Team"),
 *     @OA\Property(property="dark_mode", type="boolean", example=false),
 *     @OA\Property(property="expires_at", type="string", format="date-time", nullable=true, example="2025-01-01T00:00:00Z"),
 *     @OA\Property(property="locale", type="string", example="en_US"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-10T15:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-04-10T12:00:00Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(
 *         property="role",
 *         ref="#/components/schemas/Role"
 *     )
 * )
 * @property int $id
 * @property string $username
 * @property string $password
 * @property string $email
 * @property string $firstname
 * @property string $lastname
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $last_login
 * @property string|null $last_login_ip
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property string|null $signature
 * @property int $dark_mode
 * @property string $role_id
 * @property string $locale
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read string $full_name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Metadata> $metadata
 * @property-read int|null $metadata_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\Admin\Role|null $role
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\Admin\AdminFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereDarkMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereFirstname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereLastLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereLastLoginIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereLastname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereSignature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin whereUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin withoutTrashed()
 * @mixin \Eloquent
 */
class Admin extends Authenticatable implements NotifiablePlaceholderInterface
{
    use CanUse2FA, HasApiTokens, HasFactory, HasMetadata, Loggable, Notifiable, softDeletes;

    protected $fillable = [
        'email',
        'password',
        'username',
        'firstname',
        'lastname',
        'email_verified_at',
        'last_login',
        'last_login_ip',
        'signature',
        'dark_mode',
        'expires_at',
        'role_id',
        'locale',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'last_login' => 'datetime',
    ];

    protected $attributes = [
        'dark_mode' => false,
        'locale' => 'fr_FR',
    ];

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordEmail($token));
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function isActive()
    {
        return $this->expires_at == null || $this->expires_at->isFuture();
    }

    public function initials()
    {
        return $this->firstname[0].$this->lastname[0];
    }

    public function can($abilities, $arguments = [])
    {
        return $this->role->hasPermission($abilities);
    }

    public function notify($instance)
    {
        try {
            app(Dispatcher::class)->send($this, $instance);
            \Cache::forget('notification_error');
        } catch (\Exception $e) {
            if ($instance instanceof MailTested) {
                throw $e;
            }
            \Cache::put('notification_error', $e->getMessage().' | Date : '.date('Y-m-d H:i:s'), 3600 * 24);
        }
    }

    public function getFullNameAttribute(): string
    {
        return $this->firstname.' '.$this->lastname;
    }

    public function excerptFullName(int $length = 20): string
    {
        return \Str::limit($this->fullname, $length);
    }

    public function getTicketSignature(Customer $customer): string
    {
        $greeting = setting('mail_greeting');
        $greeting = EmailTemplate::replacePlaceholders($greeting, $customer);

        return $greeting.PHP_EOL.PHP_EOL.$this->signature ?? '';
    }

    public static function newFactory()
    {
        return \Database\Factories\Admin\AdminFactory::new();
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}

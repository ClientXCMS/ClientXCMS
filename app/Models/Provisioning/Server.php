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

namespace App\Models\Provisioning;

use App\Casts\EncryptCast;
use App\Contracts\Notifications\HasNotifiableVariablesInterface;
use App\Models\Traits\HasMetadata;
use App\Models\Traits\Loggable;
use App\Models\Traits\ModelStatutTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @OA\Schema (
 *     schema="ProvisioningServer",
 *     title="Provisioning Server",
 *     description="A server entity used for service provisioning, including credentials and technical specifications.",
 *     required={"name", "hostname", "address", "type"},
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Node-Paris-01"),
 *     @OA\Property(property="hostname", type="string", example="paris01.clientx.local"),
 *     @OA\Property(property="address", type="string", example="192.168.0.10"),
 *     @OA\Property(property="port", type="integer", example=443, description="Port used to communicate with the server"),
 *     @OA\Property(property="username", type="string", example="root", nullable=true),
 *     @OA\Property(property="password", type="string", example="s3cur3", nullable=true),
 *     @OA\Property(property="type", type="string", example="pterodactyl", description="Type of the provisioning integration"),
 *     @OA\Property(property="status", type="string", enum={"active", "hidden", "unreferenced"}, example="active"),
 *     @OA\Property(property="maxaccounts", type="integer", example=100, nullable=true, description="Maximum number of accounts/services on this server"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-02T12:00:00Z")
 * )
 *
 * @property int $id
 * @property string $name
 * @property int $port
 * @property $username
 * @property $password
 * @property string $type
 * @property string $address
 * @property string $hostname
 * @property int $maxaccounts
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Metadata> $metadata
 * @property-read int|null $metadata_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Provisioning\Service> $services
 * @property-read int|null $services_count
 *
 * @method static \Database\Factories\Provisioning\ServerFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Server newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Server newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Server onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Server query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Server whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Server whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Server whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Server whereHostname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Server whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Server whereMaxaccounts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Server whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Server wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Server wherePort($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Server whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Server whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Server whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Server whereUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Server withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Server withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Server extends Model implements HasNotifiableVariablesInterface
{
    use HasFactory, HasMetadata,Loggable, ModelStatutTrait, softDeletes;

    protected $fillable = [
        'name',
        'port',
        'username',
        'password',
        'type',
        'address',
        'hostname',
        'maxaccounts',
        'status',
    ];

    protected $attributes = [
        'status' => 'active',
        'port' => 443,
    ];

    protected $casts = [
        'username' => EncryptCast::class,
        'password' => EncryptCast::class,
    ];

    public static function boot()
    {
        parent::boot();
        static::deleting(function ($server) {
            $server->services()->delete();
        });
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function getNotificationVariables(): array
    {
        return [
            '%server_name%' => $this->name,
            '%server_address%' => $this->address,
            '%server_port%' => $this->port,
            '%server_username%' => $this->username,
            '%server_password%' => $this->password,
            '%server_type%' => $this->type,
        ];
    }

    public static function getNotificationContextVariables(): array
    {
        return [
            '%server_name%', '%server_address%', '%server_port%', '%server_username%', '%server_password%', '%server_type%',
        ];
    }
}

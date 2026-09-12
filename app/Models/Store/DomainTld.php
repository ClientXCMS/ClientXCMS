<?php

namespace App\Models\Store;

use App\Models\Provisioning\Server;
use App\Models\Traits\ModelStatutTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DomainTld extends Model
{
    use HasFactory;
    use ModelStatutTrait;

    protected $fillable = [
        'extension',
        'status',
        'server_id',
        'dns_management',
        'whois_privacy',
        'default_nameservers',
        'default_nameserver_ips',
        'default_dns_records',
        'apply_default_dns',
    ];

    protected $casts = [
        'dns_management' => 'boolean',
        'whois_privacy' => 'boolean',
        'default_nameservers' => 'array',
        'default_nameserver_ips' => 'array',
        'default_dns_records' => 'array',
        'apply_default_dns' => 'boolean',
    ];

    protected $attributes = [
        'status' => 'active',
        'dns_management' => true,
        'whois_privacy' => false,
        'default_nameservers' => '[]',
        'default_nameserver_ips' => '[]',
        'default_dns_records' => '[]',
        'apply_default_dns' => true,
    ];

    public function prices()
    {
        return $this->hasMany(DomainTldPrice::class);
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function normalizeExtension(): void
    {
        $this->extension = '.'.ltrim(strtolower(trim($this->extension)), '.');
    }
}

<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Model;

class DomainTldPrice extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'domain_tld_id',
        'currency',
        'action',
        'billing',
        'price',
        'setup',
    ];

    protected $casts = [
        'price' => 'float',
        'setup' => 'float',
    ];

    public function tld()
    {
        return $this->belongsTo(DomainTld::class, 'domain_tld_id');
    }
}

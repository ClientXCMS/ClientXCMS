<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DomainOperation extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = ['payload' => 'array', 'expires_at' => 'datetime'];
}

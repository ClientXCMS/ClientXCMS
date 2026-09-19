<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueWorkerHeartbeat extends Model
{
    protected $fillable = ['connection', 'queue', 'token', 'dispatched_at', 'processed_at'];

    protected $casts = [
        'dispatched_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}

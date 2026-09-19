<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;

class ElectronicDocumentEvent extends Model
{
    protected $fillable = [
        'electronic_document_id', 'provider_event_id', 'provider_status', 'internal_status',
        'event_code', 'message', 'payload', 'occurred_at', 'received_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(ElectronicDocument::class, 'electronic_document_id');
    }
}

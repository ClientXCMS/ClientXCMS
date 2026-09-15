<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;

class ElectronicDocument extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'documentable_type', 'documentable_id', 'provider', 'provider_document_id',
        'format', 'status', 'idempotency_key', 'payload_sha256',
        'structured_document_path', 'submitted_at', 'delivered_at', 'rejected_at',
        'last_error_code', 'last_error_message', 'raw_response',
    ];

    protected $casts = [
        'raw_response' => 'array',
        'submitted_at' => 'datetime',
        'delivered_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function documentable()
    {
        return $this->morphTo();
    }

    public function events()
    {
        return $this->hasMany(ElectronicDocumentEvent::class);
    }

    public static function idempotencyKey(Invoice|CreditNote $document, string $provider, string $payloadHash): string
    {
        return hash('sha256', implode('|', [$provider, $document::class, $document->getKey(), $document->identifier(), $payloadHash]));
    }
}

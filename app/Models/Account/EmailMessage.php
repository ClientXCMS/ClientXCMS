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

use App\Models\Admin\EmailTemplate;
use Database\Factories\Core\EmailMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $recipient
 * @property int|null $recipient_id
 * @property string $subject
 * @property EmailTemplate|null $template
 * @property string $content
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account\Customer|null $customer
 *
 * @method static \Database\Factories\Core\EmailMessageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailMessage whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailMessage whereRecipient($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailMessage whereRecipientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailMessage whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailMessage whereTemplate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailMessage whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class EmailMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'content',
        'recipient',
        'recipient_id',
        'template',
    ];

    public function template()
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'recipient_id')->withTrashed();
    }

    public static function newFactory()
    {
        return EmailMessageFactory::new();
    }
}

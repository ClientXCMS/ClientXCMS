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
namespace App\Models\Account;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $send_at
 * @property string $subject
 * @property string $content
 * @property string|null $button_text
 * @property string|null $button_url
 * @property string $conditions
 * @property string|null $already_sent_to
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailJob newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailJob newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailJob query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailJob whereAlreadySentTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailJob whereButtonText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailJob whereButtonUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailJob whereConditions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailJob whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailJob whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailJob whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailJob whereSendAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailJob whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailJob whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailJob whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class EmailJob extends Model
{
    use HasFactory;

    protected $table = 'email_jobs';

    public $timestamps = true;

    protected $fillable = [
        'send_at',
        'subject',
        'content',
        'button_text',
        'button_url',
        'condition',
        'emails',
        'already_sent_to',
        'status',
        'job_id',
    ];

    protected $casts = [
        'send_at' => 'datetime',
        'emails' => 'array',
    ];

    public static function createFromRequest(array $data): EmailJob
    {
        $job = new EmailJob;
        $job->send_at = $data['send_at'];
        $job->subject = $data['subject'];
        $job->content = $data['content'];
        $job->button_text = $data['button_text'];
        $job->button_url = $data['button_url'];
        $job->condition = $data['condition'];
        $job->emails = explode(',', $data['selected_emails']);
        $job->save();

        return $job;
    }
}

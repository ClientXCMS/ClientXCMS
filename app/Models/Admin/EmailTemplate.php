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

namespace App\Models\Admin;

use App\Contracts\Notifications\NotifiablePlaceholderInterface;
use App\Theme\ThemeManager;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;

/**
 * @property int $id
 * @property string $name
 * @property string $content
 * @property string $subject
 * @property string|null $button_text
 * @property string $locale
 * @property int $hidden
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereButtonText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereHidden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subject',
        'content',
        'button_text',
        'button_url',
        'hidden',
        'locale',
    ];

    public static function getMailMessage(string $name, string $url, array $context = [], ?NotifiablePlaceholderInterface $notifiable = null, ?string $locale = null): MailMessage
    {
        if ($locale == null && $notifiable == null) {
            $locale = setting('app_default_locale');
        } else {
            $locale = $locale ?? $notifiable->getLocale();
        }
        $template = self::where('name', $name)->where('locale', $locale)->first();
        if ($template == null) {
            $template = self::where('name', $name)->where('locale', 'en_GB')->first();
            if ($template == null) {
                throw new \Exception(sprintf('Email template %s not found for locale %s', $name, $locale));
            }
        }
        $content = self::bladeRender($template->content, $context);
        $parts = explode(PHP_EOL, $content);
        $parts = collect($parts)->map(function ($part) {
            if (empty($part)) {
                return new HtmlString('');
            }

            return new HtmlString($part);
        });
        $mail = (new MailMessage)
            ->greeting(self::replacePlaceholders(self::bladeRender(setting('mail_greeting'), $context), $notifiable))
            ->subject(self::replacePlaceholders(self::bladeRender($template->subject, $context), $notifiable))
            ->lines($parts)
            ->salutation(self::replacePlaceholders(self::bladeRender(setting('mail_salutation'), $context), $notifiable))
            ->action($template->button_text, $url);
        $mail->viewData = [
            'button_url' => $url,
            'button_text' => $template->button_text,
            'template' => $template->id,
        ];
        if (setting('email_template_name') != null) {
            $colors = ThemeManager::getColorsArray();
            $mail->view('notifications::'.str_replace('.blade', '', setting('email_template_name')), array_merge($mail->viewData, ['primaryColor' => $colors['600'], 'secondaryColor' => $colors['400']]));
        }

        return $mail;
    }

    public static function replacePlaceholders(string $content, NotifiablePlaceholderInterface $notifiable): string
    {
        $context = [
            'firstname' => $notifiable->firstname,
            'lastname' => $notifiable->lastname,
            'email' => $notifiable->email,
            'fullname' => $notifiable->FullName,
        ];
        foreach ($context as $key => $value) {
            $content = str_replace('%'.$key.'%', $value, $content);
        }

        return $content;
    }

    public static function saveTemplate(string $name, UploadedFile $file)
    {
        $folder = resource_path('views/vendor/notifications');
        $oldTemplate = setting('email_template_name');
        $file->storeAs('', $name.'.php', ['disk' => 'email']);
        if ($oldTemplate != null && $oldTemplate != $name) {
            $oldTemplate = $folder.'/'.$oldTemplate.'.blade.php';
            if (file_exists($oldTemplate)) {
                unlink($oldTemplate);
            }
        }
    }

    public static function removeTemplate()
    {
        $folder = resource_path('views/vendor/notifications');
        $oldTemplate = setting('email_template_name');
        if ($oldTemplate != null) {
            $oldTemplate = $folder.'/'.$oldTemplate.'.blade.php';
            if (file_exists($oldTemplate)) {
                unlink($oldTemplate);
            }
        }
    }

    private static function bladeRender(string $content, array $context = []): string
    {
        if (str_contains($content, '%%')) {
            $content = str_replace('%%', '%', $content);
        }
        $content = sanitize_content($content);

        return \Blade::render($content, $context, true);
    }
}

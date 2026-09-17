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
use App\Contracts\Notifications\ProvidesMailData;
use App\Services\Mail\TemplateRenderer;
use App\Theme\ThemeManager;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;
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
        $data = self::prepareData($context, $locale);
        $content = self::render($template->content, $data);
        $parts = explode(PHP_EOL, $content);
        $parts = collect($parts)->map(function ($part) {
            if (empty($part)) {
                return new HtmlString('');
            }

            return new HtmlString($part);
        });
        $mail = (new MailMessage)
            ->greeting(self::replacePlaceholders(self::render(setting('mail_greeting'), $data), $notifiable))
            ->subject(self::replacePlaceholders(self::render($template->subject, $data), $notifiable))
            ->lines($parts)
            ->salutation(self::replacePlaceholders(self::render(setting('mail_salutation'), $data), $notifiable));

        $hasCta = ! empty($url) && ! empty($template->button_text);
        if ($hasCta) {
            $mail->action($template->button_text, $url);
        }

        $mail->viewData = array_filter([
            'button_url' => $hasCta ? $url : null,
            'button_text' => $hasCta ? $template->button_text : null,
            'template' => $template->id,
        ], static fn ($value) => $value !== null);

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

    public static function removeTemplate(?string $template = null)
    {
        $folder = resource_path('views/vendor/notifications');
        $files = [
            $template.'.blade.php',
            $template.'_config.blade.php',
            $template.'_config.php',
        ];
        foreach ($files as $file) {
            $file = $folder.'/'.$file;
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public static function getConfigFilePath(): ?string
    {
        $folder = 'views/vendor/notifications';
        $file = $folder.'/'.setting('email_template_name').'_config.blade.php';
        if (! file_exists(resource_path($file))) {
            return null;
        }

        return 'vendor.notifications.'.setting('email_template_name').'_config';
    }

    public static function getConfigRules(): array
    {
        $folder = resource_path('views/vendor/notifications');
        $file = $folder.'/'.setting('email_template_name').'_config.php';
        if (! file_exists($file)) {
            return [];
        }
        $config = require $file;

        return $config;
    }

    private static function render(string $content, array $data): string
    {
        if (str_contains($content, '%%')) {
            $content = str_replace('%%', '%', $content);
        }

        return app(TemplateRenderer::class)->render($content, $data);
    }

    /**
     * Turns what a notification passes in into something a template may read.
     *
     * Models answer for themselves through the prepared-view contract. Anything
     * else that is not a scalar or an array is dropped rather than handed over:
     * an object in the context would put the template one property away from
     * whatever that object can reach.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private static function prepareData(array $context, ?string $locale): array
    {
        $data = [];
        foreach ($context as $key => $value) {
            if ($value instanceof ProvidesMailData) {
                $data[$key] = $value->toMailData($locale);

                continue;
            }
            if (is_scalar($value) || $value === null) {
                $data[$key] = $value;

                continue;
            }
            if (is_array($value)) {
                $data[$key] = self::prepareData($value, $locale);

                continue;
            }
            Log::warning('Mail context entry dropped: a template may only read scalars and arrays.', [
                'key' => $key,
                'type' => get_debug_type($value),
            ]);
        }

        return $data;
    }
}

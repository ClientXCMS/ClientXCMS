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
namespace App\Notifications;

use App\Models\Account\Customer;
use App\Models\Admin\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class CustomMail extends Notification implements ShouldQueue
{
    use Queueable;

    private array $variables;

    private string $buttonText;

    private string $buttonUrl;

    private string $content;

    private string $subject;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $variables, string $content, string $buttonText = '', string $buttonUrl = '', string $subject = '')
    {
        $this->variables = $variables;
        $this->content = $this->replaceVariables($variables, $content);
        $this->buttonText = $this->replaceVariables($variables, $buttonText);
        $this->buttonUrl = $this->replaceVariables($variables, $buttonUrl);
        $this->subject = $this->replaceVariables($variables, $subject);
    }

    public static function fromRequest(array $variables, array $request): self
    {
        return new self($variables, $request['content'] ?? '', $request['button_text'] ?? '', $request['button_url'] ?? '', $request['subject'] ?? '');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(Customer $notifiable): MailMessage
    {
        $mailMessage = EmailTemplate::getMailMessage('custom', $this->buttonUrl, [], $notifiable);
        $parts = explode(PHP_EOL, $this->content);
        $parts = collect($parts)->map(function ($part) {
            if (empty($part)) {
                return new HtmlString('');
            }

            return new HtmlString($part.'<br>');
        });
        $mailMessage->lines($parts);
        if (! empty($this->buttonText) && ! empty($this->buttonUrl)) {
            $mailMessage->action($this->buttonText, $this->buttonUrl);
        }
        $mailMessage->subject($this->subject);

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }

    private function replaceVariables(array $variables, string $content): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace($key, $value, $content);
        }

        return $content;
    }
}

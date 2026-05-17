<?php

namespace App\Mail\Account;

use App\Contracts\Notifications\NotifiablePlaceholderInterface;
use Illuminate\Notifications\Notifiable;

class EmailAddressNotifiable implements NotifiablePlaceholderInterface
{
    use Notifiable;

    public function __construct(
        public string $email,
        public ?string $firstname = null,
        public ?string $lastname = null,
        public ?string $locale = null,
    ) {}

    public function routeNotificationForMail(?object $notification = null): string
    {
        return $this->email;
    }

    public function getFullNameAttribute(): string
    {
        $name = trim(($this->firstname ?? '').' '.($this->lastname ?? ''));

        return $name !== '' ? $name : $this->email;
    }

    public function excerptFullName(int $length = 24): string
    {
        return \Str::limit($this->getFullNameAttribute(), $length);
    }

    public function getLocale(): string
    {
        return $this->locale ?? setting('app_default_locale', 'en_GB');
    }

    public function __get(string $key): mixed
    {
        if (in_array($key, ['fullName', 'FullName'], true)) {
            return $this->getFullNameAttribute();
        }

        return $this->{$key} ?? null;
    }
}

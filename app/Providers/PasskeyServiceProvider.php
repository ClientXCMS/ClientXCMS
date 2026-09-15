<?php

namespace App\Providers;

use App\Models\Account\Customer;
use App\Models\ActionLog;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Events\PasskeyDeleted;
use Laravel\Passkeys\Events\PasskeyRegistered;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;

class PasskeyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Passkeys::ignoreRoutes();
    }

    public function boot(): void
    {
        Passkeys::useUserModel(Customer::class);
        Passkeys::authorizeLoginUsing(fn ($request, PasskeyUser $user): bool => setting('passkeys_enabled', false) && $user instanceof Customer && ! $user->isBanned() && ! $user->trashed()
        );

        Event::listen(PasskeyRegistered::class, fn (PasskeyRegistered $event) => $this->log(ActionLog::PASSKEY_REGISTERED, $event->user, $event->passkey));
        Event::listen(PasskeyDeleted::class, fn (PasskeyDeleted $event) => $this->log(ActionLog::PASSKEY_DELETED, $event->user, $event->passkey));
    }

    private function log(string $action, $user, Passkey $passkey): void
    {
        if ($user instanceof Customer) {
            ActionLog::log($action, Passkey::class, $passkey->id, null, $user->id, ['name' => $passkey->name]);
        }
    }
}

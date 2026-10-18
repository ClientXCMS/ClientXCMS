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

namespace App\Events\Core\Customer;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched before a customer account is created (web + client API).
 * Listeners may refuse the registration with deny(): the controller then
 * returns the message instead of creating the account.
 */
class CustomerRegistering
{
    use Dispatchable;

    private ?string $denyMessage = null;

    /**
     * @param  array<string, mixed>  $data  validated registration data (no password)
     */
    public function __construct(
        public readonly array $data,
        public readonly ?string $ip,
    ) {}

    public function deny(string $message): void
    {
        $this->denyMessage ??= $message;
    }

    public function isDenied(): bool
    {
        return $this->denyMessage !== null;
    }

    public function message(): ?string
    {
        return $this->denyMessage;
    }
}

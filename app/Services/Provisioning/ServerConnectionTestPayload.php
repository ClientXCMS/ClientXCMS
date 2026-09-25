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

namespace App\Services\Provisioning;

use App\Exceptions\CustomTargetRequiresCredentialsException;
use App\Models\Provisioning\Server;

class ServerConnectionTestPayload
{
    private const INHERITABLE = ['address', 'port', 'hostname'];

    private const CREDENTIALS = ['username', 'password'];

    /**
     * @throws CustomTargetRequiresCredentialsException
     */
    public function resolve(array $input, ?Server $server): array
    {
        if ($server === null) {
            return $input;
        }

        // Stored credentials only ever travel to the stored host: borrowing them for an address the caller chose would leak them.
        $customTarget = $this->targetsAnotherHost($input, $server);

        if ($customTarget && ! $this->hasCredentials($input)) {
            throw new CustomTargetRequiresCredentialsException;
        }

        $fields = $customTarget ? self::INHERITABLE : array_merge(self::INHERITABLE, self::CREDENTIALS);

        foreach ($fields as $field) {
            if (! $this->filled($input, $field)) {
                $input[$field] = $server->{$field};
            }
        }

        return $input;
    }

    private function targetsAnotherHost(array $input, Server $server): bool
    {
        foreach (['address', 'hostname'] as $field) {
            if ($this->filled($input, $field) && $this->normalize($input[$field]) !== $this->normalize($server->{$field})) {
                return true;
            }
        }

        return false;
    }

    private function hasCredentials(array $input): bool
    {
        foreach (self::CREDENTIALS as $field) {
            if (! $this->filled($input, $field)) {
                return false;
            }
        }

        return true;
    }

    private function filled(array $input, string $field): bool
    {
        return array_key_exists($field, $input) && trim((string) $input[$field]) !== '';
    }

    private function normalize(mixed $value): string
    {
        return strtolower(trim((string) $value));
    }
}

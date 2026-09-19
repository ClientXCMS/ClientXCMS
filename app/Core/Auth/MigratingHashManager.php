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

namespace App\Core\Auth;

use Illuminate\Hashing\HashManager;

/**
 * Verifies a password with the algorithm its stored hash was made with, while
 * new hashes keep following the configured driver. Without this, changing
 * hash_driver locks out every account whose hash predates the change, since
 * the hashers refuse a digest they did not produce.
 */
class MigratingHashManager extends HashManager
{
    /**
     * password_get_info() algorithm names mapped to Laravel hashing drivers.
     */
    private const DRIVER_BY_ALGORITHM = [
        'bcrypt' => 'bcrypt',
        'argon2i' => 'argon',
        'argon2id' => 'argon2id',
    ];

    public function check(#[\SensitiveParameter] $value, $hashedValue, array $options = []): bool
    {
        $driver = $this->driverForHash($hashedValue);

        if ($driver === null) {
            return parent::check($value, $hashedValue, $options);
        }

        return $this->driver($driver)->check($value, $hashedValue, $options);
    }

    /**
     * @return array<int, string> the drivers usable on this PHP build
     */
    public static function availableDrivers(): array
    {
        $available = ['bcrypt'];
        foreach (password_algos() as $algorithm) {
            $driver = self::DRIVER_BY_ALGORITHM[$algorithm] ?? null;
            if ($driver !== null) {
                $available[] = $driver;
            }
        }

        return array_values(array_unique($available));
    }

    private function driverForHash(?string $hashedValue): ?string
    {
        if ($hashedValue === null || $hashedValue === '') {
            return null;
        }

        return self::DRIVER_BY_ALGORITHM[password_get_info($hashedValue)['algoName'] ?? ''] ?? null;
    }
}

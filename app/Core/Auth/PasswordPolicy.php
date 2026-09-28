<?php

namespace App\Core\Auth;

use Illuminate\Validation\Rules\Password;

class PasswordPolicy
{
    public const SETTING = 'password_security_level';

    public const COMPATIBILITY = 'compatibility';

    public const STANDARD = 'standard';

    public const REINFORCED = 'reinforced';

    public const STRICT = 'strict';

    public const LEVELS = [self::COMPATIBILITY, self::STANDARD, self::REINFORCED, self::STRICT];

    public static function configuredLevel(): ?string
    {
        $level = setting(self::SETTING, null);

        return is_string($level) && in_array($level, self::LEVELS, true) ? $level : null;
    }

    public static function account(): Password
    {
        $rule = self::rule(self::configuredLevel(), 12, 72);

        return app()->isProduction() ? $rule->uncompromised() : $rule;
    }

    public static function vm(int $legacyMinimum, int $maximum = 512): Password
    {
        return self::rule(self::configuredLevel(), $legacyMinimum, $maximum);
    }

    public static function requirements(?string $level = null): array
    {
        $level ??= self::configuredLevel();

        return match ($level) {
            self::COMPATIBILITY => ['minimum' => 8, 'mixedCase' => false, 'numbers' => false, 'symbols' => false],
            self::STANDARD => ['minimum' => 12, 'mixedCase' => true, 'numbers' => true, 'symbols' => false],
            self::REINFORCED => ['minimum' => 14, 'mixedCase' => true, 'numbers' => true, 'symbols' => true],
            self::STRICT => ['minimum' => 16, 'mixedCase' => true, 'numbers' => true, 'symbols' => true],
            default => ['minimum' => 12, 'mixedCase' => false, 'numbers' => false, 'symbols' => false],
        };
    }

    public static function generatorAttributes(): array
    {
        $requirements = self::requirements();

        return [
            'data-password-length' => $requirements['minimum'],
            'data-password-mixed-case' => $requirements['mixedCase'] ? 'true' : 'false',
            'data-password-numbers' => $requirements['numbers'] ? 'true' : 'false',
            'data-password-symbols' => $requirements['symbols'] ? 'true' : 'false',
        ];
    }

    public static function generate(?string $level = null): string
    {
        $requirements = self::requirements($level);
        $sets = ['abcdefghijklmnopqrstuvwxyz'];
        if ($requirements['mixedCase']) {
            $sets[] = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }
        if ($requirements['numbers']) {
            $sets[] = '0123456789';
        }
        if ($requirements['symbols']) {
            $sets[] = '$@!%*?&';
        }

        $all = implode('', $sets).'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789$@!%*?&';
        $characters = array_map(fn (string $set) => $set[random_int(0, strlen($set) - 1)], $sets);
        while (count($characters) < $requirements['minimum']) {
            $characters[] = $all[random_int(0, strlen($all) - 1)];
        }
        shuffle($characters);

        return implode('', $characters);
    }

    private static function rule(?string $level, int $legacyMinimum, int $maximum): Password
    {
        if ($level === null) {
            return Password::min($legacyMinimum)->max($maximum);
        }
        $requirements = self::requirements($level);
        $rule = Password::min($requirements['minimum'])->max($maximum);
        if ($requirements['mixedCase']) {
            $rule->mixedCase();
        }
        if ($requirements['numbers']) {
            $rule->numbers();
        }
        if ($requirements['symbols']) {
            $rule->symbols();
        }

        return $rule;
    }
}

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

namespace App\Core\Domain;

/**
 * Nameserver hostnames reach us from three entry points. They are normalised
 * before being validated, so the pattern itself rejects the trailing dot.
 */
final class Nameserver
{
    public const REGEX = '/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/i';

    public const RULE = 'regex:'.self::REGEX;

    public static function normalize(mixed $hostname): string
    {
        return strtolower(rtrim(trim((string) $hostname), '.'));
    }

    /**
     * @return array<int, string>
     */
    public static function normalizeAll(mixed $hostnames): array
    {
        if (! is_array($hostnames)) {
            return [];
        }

        return array_values(array_filter(array_map(self::normalize(...), $hostnames)));
    }
}

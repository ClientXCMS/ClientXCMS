<?php

namespace App\Core\Domain\Concerns;

use RuntimeException;

/** Stateless DNS record ids for registrars whose API has no stable record identifier. */
trait HandlesDnsRecordIds
{
    protected function recordId(array $record): string
    {
        return rtrim(strtr(base64_encode(json_encode($record, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
    }

    protected function decodeRecordId(string $recordId): array
    {
        $padding = strlen($recordId) % 4;
        $decoded = base64_decode(strtr($recordId.($padding ? str_repeat('=', 4 - $padding) : ''), '-_', '+/'), true);
        $record = $decoded === false ? null : json_decode($decoded, true);
        // Ids come from the client: a malformed one must never reach the registrar API.
        if (! is_array($record) || ! isset($record['name'], $record['type'], $record['value'])) {
            throw new RuntimeException('Invalid DNS record identifier');
        }

        return $record;
    }
}

<?php

namespace App\Core\Domain\Concerns;

use App\DTO\Domain\DomainCatalogPage;

/** Shapes registrar price lists into the rows LoadDomainCatalog accepts. */
trait BuildsDomainCatalog
{
    /**
     * LoadDomainCatalog fails the whole import on the first invalid row, so rows it would reject are dropped here.
     * A zero cost means "not sold" for most registrars and would publish a margin-only selling price.
     */
    protected function catalogRow(string $extension, string $action, mixed $cost, mixed $currency): ?array
    {
        $extension = '.'.ltrim(strtolower(trim($extension)), '.');
        $currency = strtoupper(trim((string) $currency));
        $valid = in_array($action, ['register', 'renew', 'transfer'], true)
            && is_numeric($cost) && (float) $cost > 0 && (float) $cost <= 99999999
            && preg_match('/^[A-Z]{3}$/', $currency) === 1
            && strlen($extension) <= 32 && preg_match('/^\.[a-z0-9-]+(?:\.[a-z0-9-]+)*$/', $extension) === 1;
        if (! $valid) {
            return null;
        }

        return ['extension' => $extension, 'action' => $action, 'billing' => 'annually', 'cost' => (float) $cost, 'currency' => $currency];
    }

    /** Registrars without a paginated price list return everything on the first page. */
    protected function singleCatalogPage(array $rows, int $offset): DomainCatalogPage
    {
        $rows = $offset === 0 ? array_values(array_filter($rows)) : [];

        return new DomainCatalogPage($rows, null, count($rows));
    }

    /** @param array<int, string> $extensions */
    protected function onlyExtensions(array $rows, array $extensions): array
    {
        $wanted = array_map(fn ($extension) => '.'.ltrim(strtolower(trim((string) $extension)), '.'), $extensions);

        return array_values(array_filter($rows, fn ($row) => is_array($row) && in_array($row['extension'], $wanted, true)));
    }
}

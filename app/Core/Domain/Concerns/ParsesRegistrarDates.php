<?php

namespace App\Core\Domain\Concerns;

use Carbon\Carbon;
use Throwable;

trait ParsesRegistrarDates
{
    /** Unparseable registrar dates become null so a sync never overwrites a known date with garbage. */
    protected function carbonDate(mixed $date): ?Carbon
    {
        if (! is_string($date) || trim($date) === '') {
            return null;
        }
        // "2026/10/24" (Internet.bs) and "2026-10-24 T10:00" (DomainNameAPI) are not ISO 8601.
        $date = str_replace([' T', '/'], ['T', '-'], trim($date));
        try {
            return Carbon::parse($date);
        } catch (Throwable) {
            return null;
        }
    }

    protected function dateString(mixed $date): ?string
    {
        return $this->carbonDate($date)?->toDateString();
    }
}

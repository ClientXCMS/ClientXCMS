<?php

namespace App\Contracts\Billing;

use Carbon\CarbonInterface;

interface EReportingScheduleInterface
{
    public function periodFor(string $type, CarbonInterface $date, string $regime, string $timezone = 'Europe/Paris'): array;
}

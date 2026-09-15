<?php

namespace Tests\Unit\Services\Billing;

use App\Services\Billing\EReportingScheduleService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EReportingScheduleServiceTest extends TestCase
{
    #[DataProvider('periods')]
    public function test_it_computes_official_reporting_windows(string $type, string $date, string $regime, string $start, string $end, string $due): void
    {
        $period = (new EReportingScheduleService)->periodFor($type, CarbonImmutable::parse($date, 'Europe/Paris'), $regime);
        $this->assertSame($start, $period['start']);
        $this->assertSame($end, $period['end']);
        $this->assertSame($due, $period['due_at']->toDateString());
    }

    public static function periods(): array
    {
        return [
            'first decade' => ['transaction', '2026-02-05', 'real_normal_monthly', '2026-02-01', '2026-02-10', '2026-02-20'],
            'second decade February' => ['transaction', '2026-02-15', 'real_normal_monthly', '2026-02-11', '2026-02-20', '2026-02-28'],
            'last decade' => ['transaction', '2026-12-25', 'real_normal_monthly', '2026-12-21', '2026-12-31', '2027-01-10'],
            'quarterly monthly transmission' => ['transaction', '2026-04-12', 'real_normal_quarterly', '2026-04-01', '2026-04-30', '2026-05-10'],
            'franchise bimonthly' => ['transaction', '2026-02-12', 'franchise_base', '2026-01-01', '2026-02-28', '2026-03-25'],
            'payment bimonthly' => ['payment', '2026-12-12', 'franchise_base', '2026-11-01', '2026-12-31', '2027-01-10'],
        ];
    }
}

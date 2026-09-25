<?php

namespace App\Services\Billing;

use App\Contracts\Billing\EReportingScheduleInterface;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class EReportingScheduleService implements EReportingScheduleInterface
{
    public function periodFor(string $type, CarbonInterface $date, string $regime, string $timezone = 'Europe/Paris'): array
    {
        $day = CarbonImmutable::parse($date)->setTimezone($timezone)->startOfDay();

        if ($type === 'payment') {
            return $this->monthlyOrBimonthly($day, $regime === 'franchise_base', 10, $timezone);
        }

        return match ($regime) {
            'real_normal_monthly' => $this->decade($day, $timezone),
            'franchise_base' => $this->monthlyOrBimonthly($day, true, 25, $timezone),
            'simplified' => $this->monthlyOrBimonthly($day, false, 25, $timezone),
            default => $this->monthlyOrBimonthly($day, false, 10, $timezone),
        };
    }

    private function decade(CarbonImmutable $date, string $timezone): array
    {
        if ($date->day <= 10) {
            $start = $date->startOfMonth();
            $end = $date->setDay(10);
            $due = $date->setDay(20);
        } elseif ($date->day <= 20) {
            $start = $date->setDay(11);
            $end = $date->setDay(20);
            $due = $date->endOfMonth();
        } else {
            $start = $date->setDay(21);
            $end = $date->endOfMonth();
            $due = $date->addMonthNoOverflow()->startOfMonth()->setDay(10);
        }

        return $this->result($start, $end, $due, $timezone);
    }

    private function monthlyOrBimonthly(CarbonImmutable $date, bool $bimonthly, int $dueDay, string $timezone): array
    {
        if ($bimonthly) {
            $startMonth = $date->month % 2 === 0 ? $date->subMonthNoOverflow() : $date;
            $start = $startMonth->startOfMonth();
            $end = $start->addMonthNoOverflow()->endOfMonth();
        } else {
            $start = $date->startOfMonth();
            $end = $date->endOfMonth();
        }
        $due = $end->addMonthNoOverflow()->startOfMonth()->setDay($dueDay);

        return $this->result($start, $end, $due, $timezone);
    }

    private function result(CarbonImmutable $start, CarbonImmutable $end, CarbonImmutable $due, string $timezone): array
    {
        return ['start' => $start->toDateString(), 'end' => $end->toDateString(), 'due_at' => $due->setTimezone($timezone)->endOfDay()];
    }
}

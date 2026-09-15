<?php

namespace Tests\Feature\Console;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class SchedulerDefinitionTest extends TestCase
{
    public function test_expected_cron_tasks_are_registered(): void
    {
        $events = collect(app(Schedule::class)->events())->keyBy('description');

        foreach ([
            'clientxcms:scheduler-heartbeat',
            'clientxcms:queue-heartbeat',
            'clientxcms:scheduler-history-prune',
            'invoices:delivery',
            'services:expire',
            'services:renewals',
            'clientxcms:helpdesk-close',
            'services:notify-expiration',
            'clientxcms:invoice-delete',
            'clientxcms:purge-metadata',
            'clientxcms:purge-basket',
            'clientxcms:telemetry',
        ] as $name) {
            $this->assertTrue($events->has($name), "Scheduled task [{$name}] is missing.");
        }

        $this->assertSame('* * * * *', $events['clientxcms:scheduler-heartbeat']->expression);
        $this->assertSame('* * * * *', $events['clientxcms:queue-heartbeat']->expression);
        $this->assertSame('0 */3 * * *', $events['services:renewals']->expression);
    }
}

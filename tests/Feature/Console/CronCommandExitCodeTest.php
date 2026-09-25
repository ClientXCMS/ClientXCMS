<?php

namespace Tests\Feature\Console;

use App\Models\Admin\Setting;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CronCommandExitCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_commands_succeed_when_there_is_nothing_to_process(): void
    {
        Setting::updateSettings([
            'helpdesk_ticket_auto_close_days' => 0,
            'remove_pending_invoice' => 0,
        ], null, false);

        foreach ([
            'invoices:delivery',
            'services:expire',
            'services:renewals',
            'services:notify-expiration',
            'clientxcms:helpdesk-close',
            'clientxcms:invoice-delete',
        ] as $command) {
            $this->artisan($command)->assertExitCode(Command::SUCCESS);
        }
    }
}

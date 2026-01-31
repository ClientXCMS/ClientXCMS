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

namespace App\Console\Commands\Purge;

use Illuminate\Console\Command;

class HelpdeskCloseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clientxcms:helpdesk-close';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close helpdesk tickets.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Running services:expire at '.now()->format('Y-m-d H:i:s'));
        $this->info('Closing helpdesk tickets...');
        $days = setting('helpdesk_ticket_auto_close_days', 7);
        if ($days <= 0) {
            $this->info('Auto close is disabled.');

            return;
        }
        $date = now()->subDays($days);
        $tickets = \App\Models\Helpdesk\SupportTicket::whereIn('status', ['answered', 'open'])->where('updated_at', '<', $date)->get();
        foreach ($tickets as $ticket) {
            $ticket->close('system', null, __('helpdesk.support.ticket_auto_close', ['days' => $days]));
            $this->info('Ticket #'.$ticket->id.' closed.');
        }
        $this->info('Helpdesk tickets closed.');
    }
}

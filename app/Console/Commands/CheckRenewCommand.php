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

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckRenewCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clientxcms:check-renew';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check renew';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking renew...');
        $license = app('license')->getLicense(null, true);
        if (! $license->isValid()) {
            $this->info('License is invalid. Please renew your license.');
        } else {
            $this->info('License is valid. Thank you! New expiration date: '.$license->get('expire'));
            try {
                unlink(storage_path('suspended'));
            } catch (\Exception $e) {
            }
        }
    }
}

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

use App\Core\License\LicenseCache;
use App\Models\Admin\Permission;
use App\Models\Billing\Invoice;
use App\Models\Helpdesk\SupportTicket;
use App\Models\Provisioning\ConfigOptionService;
use App\Models\Provisioning\Service;
use App\Models\Store\Pricing;
use App\Services\Core\LocaleService;
use App\Services\Store\PricingService;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Finder\Finder;

class OnUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clientxcms:on-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'On update command for CLIENTXCMS.';

    /**
     * Execute the console command.
     */

    public function handle()
    {
        $this->info('CLIENTXCMS is up to date.');
    }

}

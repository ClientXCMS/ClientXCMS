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

use App\Models\Store\Basket\Basket;
use Illuminate\Console\Command;

class PurgeBasketCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clientxcms:purge-basket {batchSize=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge unused basket records from the database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Purging unused basket records from the database...');

        $this->purgeBasket();

        $this->info('Basket records purged successfully.');
    }

    private function purgeBasket()
    {
        $batchSize = $this->argument('batchSize');
        $this->info('Purging basket records in batches of ' . $batchSize . '...');
        $limit = now()->subWeeks(2);
        $baskets = Basket::where('created_at', '<', $limit)->whereNull('completed_at')->whereNull('user_id')->get();
        $emptyBasket = Basket::where('created_at', '<', $limit)->whereNull('completed_at')->whereNull('user_id')->whereNull('ipaddress')->get();
        $nb = $baskets->count() + $emptyBasket->count();
        $this->info('Found ' . $nb . ' basket records to purge.');
        foreach ($baskets as $basket) {
            $basket->items()->delete();
            $basket->delete();
        }
        foreach ($emptyBasket as $basket) {
            $basket->delete();
        }
        $this->info('Purged ' . $nb . ' basket records.');
    }
}

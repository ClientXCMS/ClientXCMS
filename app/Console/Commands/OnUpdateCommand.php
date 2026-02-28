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
use Illuminate\Support\Facades\Schema;

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
        if (Schema::hasColumn('customers', 'notes') && Schema::hasTable('customer_notes')) {
            $migratedCount = 0;
            \App\Models\Account\Customer::whereNotNull('notes')
                ->where('notes', '!=', '')
                ->chunk(100, function ($customers) use (&$migratedCount) {
                    foreach ($customers as $customer) {
                        if (\App\Models\Account\CustomerNote::where('customer_id', $customer->id)->exists()) {
                            continue;
                        }
                        \App\Models\Account\CustomerNote::create([
                            'customer_id' => $customer->id,
                            'author_id' => null, // Unknown author
                            'content' => $customer->notes,
                            'created_at' => $customer->updated_at ?? now(),
                            'updated_at' => $customer->updated_at ?? now(),
                        ]);
                        $migratedCount++;
                    }
                });
            if ($migratedCount > 0) {
                $this->info("Migrated {$migratedCount} customer notes to the new table.");
            }
        }

        $this->info('CLIENTXCMS is up to date.');
    }
}

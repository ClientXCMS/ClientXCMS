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
 * Year: 2025
 */
namespace App\Console\Commands;

use App\Models\Admin\Permission;
use App\Services\Core\LocaleService;
use Illuminate\Console\Command;

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
        $locales = ["es_ES", "en_GB"];
        foreach ($locales as $locale) {
            LocaleService::downloadFiles($locale);
        }
    }
}

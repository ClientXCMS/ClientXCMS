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

use App\Services\Mail\StoredTemplateMigrator;
use Illuminate\Console\Command;

class MigrateEditableContentCommand extends Command
{
    protected $signature = 'content:migrate
                            {--dry-run : Report what would change without writing anything}';

    protected $description = 'Rewrite stored mail templates into the closed template grammar';

    public function handle(StoredTemplateMigrator $migrator): int
    {
        $apply = ! $this->option('dry-run');
        $result = $migrator->migrate($apply);

        $this->newLine();
        $this->line($apply ? 'Rewritten' : 'Would rewrite');
        $this->info(sprintf('%d field(s).', $result['changed']));

        if ($result['pending'] === []) {
            $this->info('Nothing left for a human to decide.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->line('Left as it was, one line per construct:');
        $this->table(['Where', 'Construct'], $result['pending']);
        $this->newLine();
        $this->warn('These need a prepared field or a rewrite by hand. Until then they ship as literal text.');

        return self::FAILURE;
    }
}

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

use App\Models\Metadata;
use Illuminate\Console\Command;
use Schema;

class PurgeMetadataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clientxcms:purge-metadata {batchSize=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Internal command to fix special characters in the database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing special characters in the database...');

        $this->purgeMetadata();
        $this->info('Special characters fixed successfully.');
    }

    private function purgeMetadata()
    {
        $batchSize = $this->argument('batchSize');
        $this->info('Starting the purge of unused metadata...');

        Metadata::chunkById($batchSize, function ($metadataBatch) {
            $deletedCount = 0;
            foreach ($metadataBatch as $metadata) {
                if (! $this->modelExists($metadata->model_type, $metadata->model_id)) {
                    $metadata->delete();
                    $deletedCount++;
                }
            }

            $this->info("Deleted $deletedCount unused metadata records in this batch.");
        });

        $this->info('Unused metadata purged successfully.');

        return 0;

    }

    protected function modelExists($modelClass, $modelId)
    {
        if (! class_exists($modelClass)) {
            return false;
        }

        $model = new $modelClass;

        if (! Schema::hasTable($model->getTable())) {
            return false;
        }

        return $modelClass::where('id', $modelId)->exists();
    }
}

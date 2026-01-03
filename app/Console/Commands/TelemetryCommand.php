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

class TelemetryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clientxcms:telemetry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send anonymized telemetry data to the ClientXCMS server.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Running clientxcms:telemetry at '.now()->format('Y-m-d H:i:s'));

        try {
            if (env('TELEMETRY_ENABLED', 'true') === 'false') {
                $this->info('Telemetry is disabled. Skipping telemetry data sending.');

                return;
            }
            $telemetryService = app(\App\Services\TelemetryService::class);
            $result = $telemetryService->sendTelemetry();
            if (! $result) {
                $this->error('Failed to send telemetry data.');

                return;
            }
            $this->info('Telemetry data sent successfully.');
        } catch (\Exception $e) {
            $this->error('Error sending telemetry data: '.$e->getMessage());
        }
    }
}

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
namespace App\Console\Commands\Services;

use App\Models\Provisioning\Service;
use Illuminate\Console\Command;

class ExpireServicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'services:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will expire services that are due for expiration.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Running services:expire at '.now()->format('Y-m-d H:i:s'));

        $services = Service::getShouldExpire();
        foreach ($services as $service) {
            $result = $service->expire();
            if ($result->success) {
                $this->info($service->id . ' : ' . $result->message);
            } else {
                $this->error($service->id . ' : ' . $result->message);
            }
        }
        $services = Service::getShouldSuspend();
        foreach ($services as $service) {
            $result = $service->suspend(__('client.alerts.suspended_reason_expired'));
            if ($result->success) {
                $this->info($service->id . ' : ' . $result->message);
            } else {
                $this->error($service->id . ' : ' . $result->message);
            }
        }
        $services = Service::getShouldCancel();
        /** @var Service $service */
        foreach ($services as $service) {
            $service->markAsCancelled();
            $this->info('Service '.$service->id.' has been marked as cancelled.');
        }
        $services = Service::getShouldHidden();
        foreach ($services as $service) {
            $service->update([
                'status' => Service::STATUS_HIDDEN,
            ]);
            $this->info('Service '.$service->id.' has been marked as hidden.');
        }
        $this->info('Finished running services:expire at '.now()->format('Y-m-d H:i:s'));
    }
}

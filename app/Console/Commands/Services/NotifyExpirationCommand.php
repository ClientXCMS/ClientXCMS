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


namespace App\Console\Commands\Services;

use App\Models\Provisioning\Service;
use Illuminate\Console\Command;

class NotifyExpirationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'services:notify-expiration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will notify users of services that are due for expiration.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Running services:notify-expiration at '.now()->format('Y-m-d H:i:s'));
        $days = explode(',', setting('notifications_expiration_days', '7,3,1'));
        if ($days == null) {
            return;
        }
        /** @var Service[] $services */
        $services = Service::getShouldNotifyExpiration($days);
        foreach ($services as $service) {
            $this->info("Notifying service {$service->id} of expiration.");
            if ($service->notifyExpiration()) {
                $this->info("Service {$service->id} notified of expiration");
            } else {
                $this->error("Service {$service->id} was not notified of expiration");
            }
        }

        $services = Service::getSubscriptionCanBeRenew();
        foreach ($services as $service) {
            $subscription = $service->subscription;
            if (! $subscription) {
                continue;
            }
            if ($subscription->state !== 'active') {
                continue;
            }
            try {
                $subscription->tryRenew();
                $this->info('Service '.$service->id.' has been renewed by subscription.');
                $service->detachMetadata('renewal_error');
                $service->detachMetadata('renewal_tries');
                $service->detachMetadata('renewal_last_try');
            } catch (\Exception $e) {
                $service->attachMetadata('renewal_error', $e->getMessage().' | last tried at '.now()->format('Y-m-d H:i:s'));
                $service->attachMetadata('renewal_tries', $service->getMetadata('renewal_tries', 0) + 1);
                $service->attachMetadata('renewal_last_try', now()->format('Y-m-d H:i:s'));
                if ($service->getMetadata('renewal_tries', 0) >= setting('max_subscription_tries') && setting('max_subscription_tries') > 0) {
                    $service->getSubscription()->cancel();
                    $this->error('Subscription '.$service->id.' has been marked as cancelled due to renewal error.');
                }
                logger()->error($e->getMessage());
                $this->error('Service '.$service->id.' failed to renew by subscription. : '.$e->getMessage());
            }
        }
    }
}

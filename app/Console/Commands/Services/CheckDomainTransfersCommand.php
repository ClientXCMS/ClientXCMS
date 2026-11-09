<?php

namespace App\Console\Commands\Services;

use App\Services\Domain\DomainTransferMonitor;
use Illuminate\Console\Command;

class CheckDomainTransfersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'services:domain-transfers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will activate domain services whose incoming transfer has completed.';

    /**
     * Execute the console command.
     */
    public function handle(DomainTransferMonitor $monitor): int
    {
        $successful = true;
        $this->info('Running services:domain-transfers at '.now()->format('Y-m-d H:i:s'));
        foreach ($monitor->pendingServices() as $service) {
            // One unreachable registrar must not block the other transfers.
            try {
                $status = $monitor->check($service);
                $this->info("Domain transfer for service #{$service->id}: ".($status?->value ?? 'not supported'));
            } catch (\Throwable $exception) {
                $successful = false;
                logger()->error($exception->getMessage());
                $this->error("Failed to check domain transfer for service #{$service->id}: {$exception->getMessage()}");
            }
        }

        return $successful ? self::SUCCESS : self::FAILURE;
    }
}

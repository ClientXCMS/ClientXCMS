<?php

namespace App\Services\Domain;

use App\Contracts\Domain\DomainTransferStatusInterface;
use App\Core\Domain\DomainTransferStatus;
use App\DTO\Provisioning\ServiceStateChangeDTO;
use App\Events\Core\Service\ServiceDeliveryFailed;
use App\Models\Provisioning\Service;
use Illuminate\Support\Collection;

/** Moves submitted domain transfers out of the pending state once their registrar reports an outcome. */
class DomainTransferMonitor
{
    public function __construct(private readonly DomainRegistrarManager $registrars) {}

    /** @return Collection<int, Service> */
    public function pendingServices(): Collection
    {
        return Service::query()
            ->where('type', 'domain')
            ->where('status', Service::STATUS_PENDING)
            ->where('data->registrar_status', 'transfer_pending')
            ->get();
    }

    /** Returns null when the registrar cannot report transfer progress. Registrar errors are thrown. */
    public function check(Service $service): ?DomainTransferStatus
    {
        $registrar = $this->registrars->fromService($service);
        if (! $registrar instanceof DomainTransferStatusInterface) {
            return null;
        }
        $report = $registrar->transferStatus($service);
        if ($report->status === DomainTransferStatus::Completed) {
            $this->complete($service);
        }
        if ($report->status === DomainTransferStatus::Failed) {
            $this->fail($service, $report->message ?: 'Domain transfer failed');
        }

        return $report->status;
    }

    private function complete(Service $service): void
    {
        $data = $service->data ?? [];
        $data['registrar_status'] = 'active';
        unset($data['transfer_error']);
        $service->data = $data;
        $service->markDelivered(new ServiceStateChangeDTO($service, true, 'Domain transfer completed'));
        $service->save();
    }

    /** The service stays pending so an admin can retry with a new auth code: createAccount() resubmits it. */
    private function fail(Service $service, string $message): void
    {
        $data = $service->data ?? [];
        $data['registrar_status'] = 'transfer_failed';
        $data['transfer_error'] = $message;
        unset($data['transfer_submitted'], $data['registrar_id']);
        $service->data = $data;
        $service->delivery_errors = $message;
        $service->save();
        event(new ServiceDeliveryFailed($service, new ServiceStateChangeDTO($service, false, $message)));
    }
}

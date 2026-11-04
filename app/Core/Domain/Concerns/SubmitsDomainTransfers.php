<?php

namespace App\Core\Domain\Concerns;

use App\Models\Provisioning\Service;
use RuntimeException;

/** Service state shared by every registrar transfer, matching what ServerTypeTrait::deliver() expects. */
trait SubmitsDomainTransfers
{
    protected function transferAuthCode(Service $service): string
    {
        $authCode = trim((string) (($service->data ?? [])['auth_code'] ?? ''));
        if ($authCode === '') {
            throw new RuntimeException('Authorization code is required');
        }

        return $authCode;
    }

    /** The auth code is a secret valid until the transfer completes: it is not kept once submitted. */
    protected function markTransferSubmitted(Service $service, mixed $registrarId = null): void
    {
        $data = $service->data ?? [];
        unset($data['auth_code']);
        if ($registrarId !== null && $registrarId !== '') {
            $data['registrar_id'] = (string) $registrarId;
        }
        $data['registrar_status'] = 'transfer_pending';
        $data['transfer_submitted'] = true;
        $service->data = $data;
        $service->save();
    }
}

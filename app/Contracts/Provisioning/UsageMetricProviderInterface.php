<?php

namespace App\Contracts\Provisioning;

use App\DTO\Provisioning\UsageSnapshotDTO;
use App\Models\Provisioning\Service;

interface UsageMetricProviderInterface
{
    public function usageSnapshot(Service $service): UsageSnapshotDTO;
}

<?php

namespace App\DTO\Provisioning;

final class UsageSnapshotDTO
{
    public function __construct(
        public readonly bool $running,
        public readonly ?int $memoryAllocatedBytes = null,
        public readonly ?int $memoryUsedBytes = null,
        public readonly ?int $diskAllocatedBytes = null,
        public readonly ?int $diskUsedBytes = null,
    ) {}

    public function valueFor(string $metric, string $source = 'allocated'): ?int
    {
        return match ([$metric, $source]) {
            ['memory', 'allocated'] => $this->memoryAllocatedBytes,
            ['memory', 'used'] => $this->memoryUsedBytes,
            ['disk', 'allocated'] => $this->diskAllocatedBytes,
            ['disk', 'used'] => $this->diskUsedBytes,
            default => null,
        };
    }
}

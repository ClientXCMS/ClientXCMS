<?php

namespace Tests\Fixtures;

use App\Contracts\Provisioning\ServerTypeInterface;
use App\Core\NoneProductType;

class RecordingProductType extends NoneProductType
{
    protected string $uuid = RecordingServerType::UUID;

    public function __construct(private RecordingServerType $driver) {}

    public function server(): ?ServerTypeInterface
    {
        return $this->driver;
    }
}

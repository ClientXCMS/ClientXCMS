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

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

/**
 * @extends Collection<int, Metadata>
 */
class MetadataCollection extends Collection
{
    public function toArray()
    {
        return $this->serializable()->toArray();
    }

    public function jsonSerialize(): array
    {
        return $this->serializable()->jsonSerialize();
    }

    private function serializable(): BaseCollection
    {
        return $this->toBase()->reject(fn (Metadata $metadata) => Metadata::isHiddenFromSerialization($metadata->key))->values();
    }
}

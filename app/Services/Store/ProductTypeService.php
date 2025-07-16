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
namespace App\Services\Store;

use App\Contracts\Store\ProductTypeInterface;
use Illuminate\Support\Collection;

class ProductTypeService
{
    private Collection $types;

    public function __construct()
    {
        $this->types = collect();
    }

    public function registerType(ProductTypeInterface $type): void
    {
        $this->types->put($type->uuid(), $type);
    }

    public function has(string $key): bool
    {
        return $this->types->has($key);
    }

    public function forProduct(string $type): ?ProductTypeInterface
    {
        return $this->types->get($type);
    }

    public function all(): Collection
    {
        return $this->types;
    }

    public function addProductType(ProductTypeInterface $productType): void
    {
        $this->types = $this->types->merge([$productType->uuid() => $productType]);
    }
}

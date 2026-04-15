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

namespace App\Abstracts;

use App\Contracts\Store\ProductDataInterface;
use App\DTO\Store\ProductDataDTO;

abstract class AbstractProductData implements ProductDataInterface
{
    protected array $parameters = [];

    public function primary(ProductDataDTO $productDataDTO): string
    {
        return $this->parameters[0] ?? '';
    }

    public function validate(): array
    {
        return [];
    }

    public function parameters(ProductDataDTO $productDataDTO): array
    {
        return collect($productDataDTO->parameters)->filter(function ($value, $key) {
            return in_array($key, $this->parameters);
        })->toArray();
    }

    public function render(ProductDataDTO $productDataDTO)
    {
        return 'Please override render method in your product data class';
    }

    public function renderAdmin(ProductDataDTO $productDataDTO)
    {
        return $this->render($productDataDTO);
    }
}

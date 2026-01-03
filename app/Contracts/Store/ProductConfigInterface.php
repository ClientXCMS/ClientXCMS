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

namespace App\Contracts\Store;

use App\Models\Store\Product;

interface ProductConfigInterface
{
    public function validate(): array;

    public function render(Product $product);

    public function storeConfig(Product $product, array $parameters);

    public function updateConfig(Product $product, array $parameters);

    public function deleteConfig(Product $product);

    public function getConfig(int $id, $entity = null);

    public function cloneConfig(Product $oldProduct, Product $newProduct);
}

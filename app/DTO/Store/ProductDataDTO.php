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
namespace App\DTO\Store;

use App\Models\Store\Product;

class ProductDataDTO
{
    public Product $product;

    public array $data;

    public array $options;

    public array $parameters;

    /**
     * @param  array  $data  - Data already stored in the database (if any) (basket or invoice item)
     * @param  array  $parameters  - Data from the request
     * @param  array  $options  - Additional options (if any)
     */
    public function __construct(Product $product, array $data, array $parameters, array $options = [])
    {
        $this->product = $product;
        $this->data = $data;
        $this->options = $options;
        $this->parameters = $parameters;
    }
}

<?php

namespace App\Core\Domain;

use App\Abstracts\AbstractProductType;
use App\Contracts\Store\ProductDataInterface;
use App\Contracts\Store\ProductTypeInterface;
use App\Models\Store\Product;

class DomainProductType extends AbstractProductType
{
    protected string $uuid = ProductTypeInterface::DOMAIN;

    protected string $title = 'Domain';

    protected string $type = ProductTypeInterface::DOMAIN;

    public function data(?Product $product = null): ?ProductDataInterface
    {
        return new DomainProductData;
    }

    public function panel(): ?\App\Contracts\Provisioning\PanelProvisioningInterface
    {
        return new DomainProvisioningPanel;
    }

    public function server(): ?\App\Contracts\Provisioning\ServerTypeInterface
    {
        return new DomainServerType;
    }
}

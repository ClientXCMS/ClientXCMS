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
namespace App\Abstracts;

use App\Contracts\Store\ProductConfigInterface;
use App\Contracts\Store\ProductTypeInterface;
use App\Models\Store\Product;

abstract class AbstractProductType implements ProductTypeInterface
{
    protected string $uuid;

    protected string $title;

    protected string $type;

    /**
     * {@inheritDoc}
     */
    public function uuid(): string
    {
        return $this->uuid;
    }

    /**
     * {@inheritDoc}
     */
    public function title(): string
    {
        return $this->title;
    }

    /**
     * {@inheritDoc}
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * {@inheritDoc}
     */
    public function data(?Product $product = null): ?\App\Contracts\Store\ProductDataInterface
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function panel(): ?\App\Contracts\Provisioning\PanelProvisioningInterface
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function server(): ?\App\Contracts\Provisioning\ServerTypeInterface
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function options(): array
    {
        return [];
    }

    public function config(): ?ProductConfigInterface
    {
        return null;
    }
}

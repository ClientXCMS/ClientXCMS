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

use App\Contracts\Provisioning\PanelProvisioningInterface;
use App\Contracts\Provisioning\ServerTypeInterface;
use App\Models\Store\Product;

interface ProductTypeInterface
{
    const DOWNLOAD = 'download';

    const SERVICE = 'service';

    const DOMAIN = 'domain';



    const ALL = [
        self::DOWNLOAD,
        self::SERVICE,
        self::DOMAIN,
    ];

    /**
     * @return string UUID
     */
    public function uuid(): string;

    /**
     * @return string Title
     */
    public function title(): string;

    /**
     * @return string Type of provisioning (download, service, domain, gift_card, license, none, other)
     *                Recommended : service
     */
    public function type(): string;

    /**
     * @param  Product|null  $product
     *                                 If you want ask osname or other data to the user on new order
     * @return ProductDataInterface|null Product data class (if any)
     */
    public function data(?Product $product = null): ?ProductDataInterface;

    /**
     * @return PanelProvisioningInterface|null Panel provisioning class (if any)
     */
    public function panel(): ?PanelProvisioningInterface;

    /**
     * @return ServerTypeInterface|null Server provisioning class (if any)
     *                                  Recommanded if you want to create a service
     */
    public function server(): ?ServerTypeInterface;

    /**
     * @return ProductOptionsInterface[] Product additional options class (if any)
     */
    public function options(): array;

    /**
     * @return ProductConfigInterface|null Product config class (if any)
     */
    public function config(): ?ProductConfigInterface;
}

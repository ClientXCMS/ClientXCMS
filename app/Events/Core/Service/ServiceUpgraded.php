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
namespace App\Events\Core\Service;

use App\Models\Billing\Upgrade;
use App\Models\Store\Product;

class ServiceUpgraded extends ServiceEvent
{
    public Product $old;

    public Product $new;

    public Upgrade $upgrade;

    public function __construct(Upgrade $upgrade)
    {
        parent::__construct($upgrade->service);
        $this->old = $upgrade->oldProduct;
        $this->new = $upgrade->newProduct;
        $this->upgrade = $upgrade;
    }
}

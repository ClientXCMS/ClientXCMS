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

namespace App\Http\Controllers\Admin\Billing;

use App\Http\Controllers\Admin\AbstractCrudController;
use App\Models\Billing\Upgrade;

class UpgradeController extends AbstractCrudController
{
    protected array $sort = ['created_at' => 'desc'];

    protected string $model = Upgrade::class;

    protected string $viewPath = 'admin.billing.upgrades';

    protected string $translatePrefix = 'billing.admin.upgrades';

    protected string $routePath = 'admin.billing.upgrades';

    public function getSearchFields()
    {
        return [
            'customer_id' => __('global.customer'),
            'service_id' => __('global.service'),
            'old_product_id' => __('provisioning.admin.services.upgrade.old_product'),
            'new_product_id' => __('provisioning.admin.services.upgrade.new_product'),
        ];
    }
}

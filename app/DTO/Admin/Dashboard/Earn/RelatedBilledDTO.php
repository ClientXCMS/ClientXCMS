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

namespace App\DTO\Admin\Dashboard\Earn;

use Illuminate\Support\Collection;

class RelatedBilledDTO
{
    public Collection $items;

    public array $names = [];

    public array $icons = [
        'service' => 'bi bi-box',
        'renewal' => 'bi bi-arrow-repeat',
        'upgrade' => 'bi bi-arrows-angle-expand',
        'custom_item' => 'bi bi-pen',
        'config_option' => 'bi bi-cart-plus',
        'config_option_service' => 'bi bi-boxes',
    ];

    public array $amounts = [];

    public array $percentages = [];

    public array $counts = [];

    public function __construct(Collection $items)
    {
        $this->items = $items;
        collect($this->items)->map(function ($item) {
            $percent = $item['count'] / $this->items->sum('count') * 100;
            $percent = number_format($percent);
            $name = __('admin.dashboard.earn.related_billed.'.$item['type']);
            $this->names[$item['type']] = $name;
            $this->amounts[$item['type']] = $item['amount'];
            $this->percentages[$item['type']] = $percent;
            $this->counts[$item['type']] = $item['count'];
        });
    }

    public function isEmpty()
    {
        return $this->items->isEmpty();
    }
}

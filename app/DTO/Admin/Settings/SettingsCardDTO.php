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

namespace App\DTO\Admin\Settings;

use Illuminate\Support\Collection;

class SettingsCardDTO
{
    public string $uuid;

    public string $name;

    public string $description;

    public Collection $items;

    public int $order;

    public bool $is_active;

    public int $columns;

    public string $icon;

    public function __construct(string $uuid, string $name, string $description, int $order, Collection $items, bool $is_active = true, int $columns = 2, string $icon = 'bi bi-gear')
    {
        $this->uuid = $uuid;
        $this->name = $name;
        $this->description = $description;
        $this->items = $items;
        $this->order = $order;
        $this->is_active = $is_active;
        $this->columns = $columns;
        $this->icon = $icon;
    }
}

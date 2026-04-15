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

namespace App\Core\Admin\Dashboard;

class AdminCardWidget
{
    public string $uuid;

    public $render;

    public int $cols;

    public ?string $after;

    public string $permission;

    public function __construct(string $uuid, callable $render, string $permission, int $cols = 1, ?string $after = null)
    {
        $this->uuid = $uuid;
        $this->render = $render;
        $this->cols = $cols;
        $this->after = $after;
        $this->permission = $permission;
    }

    public function render()
    {
        return call_user_func($this->render);
    }
}

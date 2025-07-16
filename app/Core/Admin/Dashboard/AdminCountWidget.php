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
namespace App\Core\Admin\Dashboard;

class AdminCountWidget
{
    public string $uuid;

    public string $title;

    public $value;

    public string $icon;

    public string $permission;

    public bool $small;

    public function __construct(string $uuid, string $icon, string $title, $value, string $permission, bool $small = false)
    {
        $this->uuid = $uuid;
        $this->title = $title;
        $this->value = $value;
        $this->icon = $icon;
        $this->permission = $permission;
        $this->small = $small;
    }

    public function value()
    {
        if (is_callable($this->value)) {
            return call_user_func($this->value);
        }

        return $this->value;
    }
}

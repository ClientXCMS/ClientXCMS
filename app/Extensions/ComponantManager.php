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

namespace App\Extensions;

use App\DTO\Core\Extensions\ExtensionDTO;
use App\DTO\Core\Extensions\ExtensionInstallDTO;
use Composer\Autoload\ClassLoader;

class ComponantManager implements ExtensionInterface
{
    public function onInstall(string $uuid): void
    {
        // TODO: Implement onInstall() method.
    }

    public function onUninstall(string $uuid): void
    {
        // TODO: Implement onUninstall() method.
    }

    public function onEnable(string $uuid): void
    {
        // TODO: Implement onEnable() method.
    }

    public function onDisable(string $uuid): void
    {
        // TODO: Implement onDisable() method.
    }

    public function download(string $uuid): ExtensionInstallDTO
    {
        // TODO: Implement download() method.
    }

    public function getExtensions(bool $enabledOnly = false): array
    {
        // TODO: Implement getExtensions() method.
    }

    public function autoload(ExtensionDTO $DTO, \Illuminate\Foundation\Application $application, ClassLoader $composer): void
    {
        // TODO: Implement autoload() method.
    }
}

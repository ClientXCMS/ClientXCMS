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
namespace App\Extensions;

use App\DTO\Core\Extensions\ExtensionDTO;
use App\DTO\Core\Extensions\ExtensionInstallDTO;
use Composer\Autoload\ClassLoader;
use Illuminate\Foundation\Application;

interface ExtensionInterface
{
    public function onInstall(string $uuid): void;

    public function onUninstall(string $uuid): void;

    public function onEnable(string $uuid): void;

    public function onDisable(string $uuid): void;

    public function download(string $uuid): ExtensionInstallDTO;

    public function autoload(ExtensionDTO $DTO, Application $application, ClassLoader $composer): void;

    public function getExtensions(bool $enabledOnly = false): array;
}

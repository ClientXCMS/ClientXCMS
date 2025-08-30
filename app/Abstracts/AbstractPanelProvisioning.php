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
namespace App\Abstracts;

use App\Contracts\Provisioning\CardAdminServiceInterface;
use App\Contracts\Provisioning\PanelProvisioningInterface;
use App\DTO\Provisioning\ProvisioningTabDTO;
use App\Models\Provisioning\Service;

abstract class AbstractPanelProvisioning implements PanelProvisioningInterface
{
    protected string $uuid;

    public function render(Service $service, array $permissions = [])
    {
        return 'Empty panel';
    }

    public function renderAdmin(Service $service)
    {
        return 'Empty panel';
    }

    public function permissions(): array
    {
        return [];
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function tabs(Service $service): array
    {
        return [];
    }

    public function cardAdmin(Service $service): ?CardAdminServiceInterface
    {
        return null;
    }

    public function getTab(Service $service, string $uuid): ?ProvisioningTabDTO
    {
        $tabs = $this->tabs($service);
        foreach ($tabs as $tab) {
            if ($tab->uuid === $uuid) {
                return $tab;
            }
        }

        return null;
    }
}

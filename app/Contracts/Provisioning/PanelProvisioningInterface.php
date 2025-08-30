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


namespace App\Contracts\Provisioning;

use App\DTO\Provisioning\ProvisioningTabDTO;
use App\Models\Provisioning\Service;

interface PanelProvisioningInterface
{
    public function uuid(): string;

    /**
     * @return array<ProvisioningTabDTO>
     */
    public function tabs(Service $service): array;

    public function render(Service $service, array $permissions = []);

    public function renderAdmin(Service $service);

    public function permissions(): array;

    public function cardAdmin(Service $service): ?CardAdminServiceInterface;

    public function getTab(Service $service, string $uuid): ?ProvisioningTabDTO;
}

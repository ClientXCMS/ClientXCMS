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

use App\DTO\Provisioning\ConnectionResponse;
use App\DTO\Provisioning\ServiceStateChangeDTO;
use App\Models\Account\Customer;
use App\Models\Billing\ConfigOption;
use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;
use App\Models\Store\Product;

interface ServerTypeInterface
{
    /**
     * @return string the uuid of the server type
     */
    public function uuid(): string;

    /**
     * @return string the title of the server type
     */
    public function title(): string;

    /**
     * @return Server|null
     *                     Find the server that this server type is associated with
     *                     TODO : add parameter with order model
     */
    public function findServer(Product $product): ?Server;

    public function testConnection(array $params): ConnectionResponse;

    /**
     * @param
     * validate data for test connection
     */
    public function validate(): array;

    /**
     * @return string
     *                Create a new account on the server
     */
    public function createAccount(Service $service): ServiceStateChangeDTO;

    /**
     * @param  Service  $service
     *                            suspend the account on the server
     */
    public function suspendAccount(Service $service): ServiceStateChangeDTO;

    /**
     * @param  Service  $service
     *                            unsuspend the account on the server
     */
    public function unsuspendAccount(Service $service): ServiceStateChangeDTO;

    /**
     * @param  Service  $service
     *                            expire the account on the server
     */
    public function expireAccount(Service $service): ServiceStateChangeDTO;

    /**
     * Trigger on service is renew
     */
    public function onRenew(Service $service): ServiceStateChangeDTO;

    /**
     * @param  string|null  $password
     *                                 change password of the account on the server if we can
     *                                 if password is null, reply success if this implementation can change password on the server.
     */
    public function changePassword(Service $service, ?string $password = null): ServiceStateChangeDTO;

    /**
     * @param  string|null  $name
     *                             change name of the account on the server if we can
     *                             if name is null, reply success if this implementation can change name on the server.
     */
    public function changeName(Service $service, ?string $name = null): ?ServiceStateChangeDTO;

    /**
     * @return ImportServiceInterface|null
     *                                     Return the import service implementation for this server type
     *                                     If null, the import service will not be available
     */
    public function importService(): ?ImportServiceInterface;

    /**
     * @return ServiceStateChangeDTO
     *                               Upgrade the service to the new product
     */
    public function upgradeService(Service $service, Product $product): ServiceStateChangeDTO;

    /**
     * @return ServiceStateChangeDTO
     *                               Add an option to the service
     */
    public function addOption(Service $service, ConfigOption $configOption): ServiceStateChangeDTO;

    /**
     * @return ServiceStateChangeDTO
     *                               Change the owner of the service
     */
    public function changeCustomer(Service $service, Customer $customer): ServiceStateChangeDTO;

    /**
     * @return array
     *               Return the supported actions for this server type
     */
    public function getSupportedOptions(): array;
}

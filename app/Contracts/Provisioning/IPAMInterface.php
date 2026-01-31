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

use App\DTO\Provisioning\AddressIPAM;
use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;
use Illuminate\Pagination\Paginator;

interface IPAMInterface
{
    public static function insertIP(AddressIPAM $address): AddressIPAM;

    public static function updateIP(AddressIPAM $address): AddressIPAM;

    public static function deleteIP(AddressIPAM $address): bool;

    public static function findById(int $id): ?AddressIPAM;

    public static function findByIP(string $ip): ?AddressIPAM;

    public static function findByService(Service $service): array;

    public static function fetchAdresses(int $nb = 1, ?Server $server = null, ?string $node = null): array;

    public static function useAddress(AddressIPAM $address, Service $service): AddressIPAM;

    public static function releaseAddress(AddressIPAM $address): AddressIPAM;

    public static function fetchAll(): Paginator;
}

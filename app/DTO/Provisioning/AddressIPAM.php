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

namespace App\DTO\Provisioning;

use App\Contracts\Provisioning\IPAMInterface;
use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;
use App\Modules\Proxmox\ProxmoxServerType;

class AddressIPAM
{
    const AVAILABLE = 'available';

    const UNAVAILABLE = 'unavailable';

    const USED = 'used';

    public ?int $id;

    public string $ip;

    public string $netmask;

    public string $gateway;

    public ?string $bridge;

    public ?int $mtu;

    public ?string $mac;

    public ?string $ipv6;

    public ?string $ipv6_gateway;

    public ?bool $is_primary;

    public ?int $service_id;

    public ?string $notes;

    public ?string $status;

    public ?int $server;

    public function __construct(array $data)
    {
        if (empty($data)) {
            return;
        }
        $this->id = $data['id'] ?? null;
        $this->ip = $data['ip'];
        $this->netmask = $data['netmask'];
        $this->gateway = $data['gateway'];
        $this->bridge = $data['bridge'] ?? null;
        $this->mtu = $data['mtu'] ?? null;
        $this->mac = $data['mac'] ?? null;
        $this->ipv6 = $data['ip6'] ?? null;
        $this->ipv6_gateway = $data['gateway6'] ?? null;
        $this->is_primary = $data['is_primary'] ?? null;
        $this->service_id = $data['service_id'] ?? null;
        $this->notes = $data['notes'] ?? null;
        $this->status = $data['status'] ?? 'available';
        $this->server = $data['server'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'ip' => $this->ip,
            'netmask' => $this->netmask,
            'gateway' => $this->gateway,
            'bridge' => $this->bridge,
            'mtu' => $this->mtu,
            'mac' => $this->mac,
            'ipv6' => $this->ipv6,
            'ipv6_gateway' => $this->ipv6_gateway,
            'is_primary' => $this->is_primary,
            'service_id' => $this->service_id,
            'notes' => $this->notes,
            'status' => $this->status,
            'server' => $this->server,
        ];
    }

    public static function getAvailableIPs(IPAMInterface $class, int $count, Server $server, string $node): array
    {
        return $class::fetchAdresses($count, $server, $node);
    }

    public static function findByService(IPAMInterface $class, Service $service): array
    {
        return $class::findByService($service);
    }

    public static function useIP(IPAMInterface $class, array $ips, Service $service): void
    {
        foreach ($ips as $i => $ip) {
            if ($i != 0) {
                $ip->is_primary = true;
            }
            $class::useAddress($ip, $service);
        }
    }

    public static function releaseIP(IPAMInterface $class, Service $service): void
    {
        $ips = self::findByService($class, $service);
        foreach ($ips as $ip) {
            $class::releaseAddress($ip);
        }
    }

    public static function fromQemuVM(?array $net = null, ?array $cloudinit = null, ?int $serviceId = null): AddressIPAM
    {
        $address = '127.0.1.1';
        $netmark = '24';
        if ($cloudinit === null || $net === null) {
            return new AddressIPAM([]);
        }
        if (array_key_exists('ip', $cloudinit)) {
            [$address, $netmark] = explode('/', $cloudinit['ip']);
        }
        $values = [
            'id' => null,
            'ip' => $address,
            'netmask' => $netmark,
            'gateway' => $cloudinit['gw'],
            'ipv6' => $cloudinit['ip6'] ?? null,
            'ipv6_gateway' => $cloudinit['gw6'] ?? null,
            'is_primary' => false,
            'service_id' => null,
            'notes' => null,
            'bridge' => $net['bridge'] ?? null,
            'mtu' => $net['mtu'] ?? null,
            'mac' => $net['virtio'] ?? null,
            'status' => self::AVAILABLE,
        ];
        if ($serviceId !== null) {
            $values['status'] = self::USED;
            $values['service_id'] = $serviceId;
        }

        if (ProxmoxServerType::getIPAMClass()::findByIP($cloudinit['ip']) !== null) {
            return new AddressIPAM($values);
        }
        ProxmoxServerType::getIPAMClass()::insertIP(new AddressIPAM($values));

        return new AddressIPAM($values);
    }

    public static function fromLxcVM(array $net, int $serviceId)
    {
        $address = '127.0.1.1';
        $netmark = '24';
        if (array_key_exists('ip', $net)) {
            [$address, $netmark] = explode('/', $net['ip']);
        }
        $values = [
            'id' => null,
            'ip' => $address,
            'netmask' => $netmark,
            'gateway' => $net['gw'],
            'ipv6' => $net['ip6'] ?? null,
            'ipv6_gateway' => $net['gw6'] ?? null,
            'is_primary' => false,
            'service_id' => $serviceId,
            'notes' => null,
            'bridge' => $net['bridge'] ?? null,
            'mtu' => $net['mtu'] ?? null,
            'mac' => $net['hwaddr'] ?? null,
            'status' => self::USED,
        ];
        if (ProxmoxServerType::getIPAMClass()::findByIP($net['ip']) !== null) {
            return new AddressIPAM($values);
        }
        ProxmoxServerType::getIPAMClass()::insertIP(new AddressIPAM($values));

        return new AddressIPAM($values);
    }
}

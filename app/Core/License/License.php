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

namespace App\Core\License;

use App\DTO\Core\Extensions\ExtensionDTO;
use App\Models\Admin\Setting;
use Carbon\Carbon;
use Exception;

class License
{
    private string $type;

    private ?string $expire;

    private ?string $domain = null;

    private array $domains = [];

    private int $lastChecked;

    private int $nextCheck;

    private array $data;

    private array $extensions;

    private ?int $serverId;

    private string $version_type;

    private ?string $supportExpiration = null;

    public function __construct(
        ?string $expire,
        ?string $supportExpiration,
        int $lastChecked,
        int $nextCheck,
        ?int $serverId,
        array $extensions,
        string $type,
        string $version_type,
        array $data
    ) {
        $this->expire = $expire;
        $this->lastChecked = $lastChecked;
        $this->nextCheck = $nextCheck;
        $this->data = $data;
        $this->serverId = $serverId;
        $this->domain = \URL::getRequest()->getHttpHost();
        $this->extensions = $extensions;
        $this->type = $type;
        $this->version_type = $version_type;
        $this->supportExpiration = $supportExpiration;

    }

    public function __serialize(): array
    {
        return [
            'expire' => $this->expire,
            'domain' => $this->domain,
            'lastchecked' => $this->lastChecked,
            'nextCheck' => $this->nextCheck,
            'domains' => $this->domains,
            'data' => $this->data,
            'server' => $this->serverId,
            'extensions' => $this->extensions,
            'type' => $this->type,
            'support_expiration' => $this->supportExpiration,
            'version_type' => $this->version_type,
        ];
    }

    public function isHit(): bool
    {
        if ($this->nextCheck < 0 || $this->lastChecked < 0 || $this->lastChecked > time()) {
            return false;
        }

        return $this->nextCheck > time();
    }

    public function get(string $property, ...$params)
    {
        $method = 'get'.ucfirst($property);
        if (method_exists($this, $method)) {
            return @$this->$method($params);
        }

        return @$this->$property;
    }

    public function set(string $property, $value)
    {
        $this->$property = $value;

        return $this;
    }

    private function getType(): string
    {
        return ucfirst($this->type);
    }

    public function isValid(): bool
    {
        return true;
    }

    public function save(string $token)
    {
        Setting::updateSettings([
            'app_license_refresh_token' => $token,
        ], null, false);
    }

    public function getModules()
    {
        return $this->getFormattedExtensions();
    }

    public function getFormattedExtensions()
    {
        $names = [];
        if (empty($this->extensions)) {
            return implode(', ', $names);
        }
        $extensions = app('extension')->getAllExtensions();
        foreach ($this->extensions as $uuid => $value) {
            /** @var ExtensionDTO $extension */
            $extension = collect($extensions)->first(fn ($extension) => $extension->uuid == $uuid);
            if ($extension == null) {
                continue;
            }
            $names[] = sprintf('%s (%s)', $extension->name(), ($value['expires_at'] != null) ? $value['expires_at'] : __('recurring.onetime'));
        }

        return implode(', ', $names);
    }

    public function getExtensions()
    {
        return $this->extensions;
    }

    public function getServer()
    {
        if ($this->serverId === null) {
            return 'local';
        }

        return str_pad($this->serverId, 2, '0', STR_PAD_LEFT);
    }

    public function getExtensionsUuids()
    {
        return collect($this->extensions)->filter(function ($extension) {
            return $extension['expires_at'] == null || Carbon::createFromFormat('d-m-y', $extension['expires_at'])->isFuture();
        })->keys()->toArray();
    }

    public function getExtensionExpiration(string $uuid)
    {
        return $this->extensions[$uuid]['expires_at'] ?? null;
    }

    public function getSupportExpiration(): ?Carbon
    {
        if ($this->supportExpiration == null) {
            return null;
        }
        try {
            return Carbon::createFromFormat('d-m-Y', $this->supportExpiration);
        } catch (Exception $e) {
            return null;
        }
    }
}

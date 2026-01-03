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

namespace App\Rules;

use App\Addons\CloudflareSubdomains\CloudflareDNSManager;

class DomainIsNotRegisted implements \Illuminate\Contracts\Validation\Rule
{
    public function __construct(private bool $subdomain = false) {}

    public function passes($attribute, $value): bool
    {
        if ($value == null) {
            return true;
        }
        if ($this->subdomain) {
            $value = $value.request()->input('subdomain');
        }
        if (! preg_match('/^(?!:\/\/)(?=.{1,255}$)((.{1,63}\.){1,127}(?![0-9]*$)[a-z0-9-]+\.?)$/i', $value)) {
            return true;
        }
        $types = app('extension')->getProductTypes();
        foreach ($types as $type) {
            if ($type->server() != null) {
                $server = $type->server();
                if ($server->isDomainRegistered($value)) {
                    return false;
                }
            }
        }
        if (app('extension')->extensionIsEnabled('cloudflaresubdomains')) {
            if (CloudflareDNSManager::existRecord($value)) {
                return false;
            }
        }

        return true;
    }

    public function message(): string
    {
        return __('validation.domain_is_registered');
    }
}

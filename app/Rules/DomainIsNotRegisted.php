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
namespace App\Rules;

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
        $types = app('extension')->getProductTypes();
        foreach ($types as $type) {
            if ($type->server() != null) {
                $server = $type->server();
                if ($server->isDomainRegistered($value)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function message(): string
    {
        return __('validation.domain_is_registered');
    }
}

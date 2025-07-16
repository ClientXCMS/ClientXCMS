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
?>
?>
?>
<h3 class="h5 font-weight-bold"> {{ __('provisioning.admin.subdomains_hosts.choose_domain') }}</h3>
@if ($subdomains->isNotEmpty())

    <div>
        <label for="domain_subdomain" class="form-label mt-2">{{ __('provisioning.admin.subdomains_hosts.use_subdomain', ['app_name' => config('app.name')]) }}</label>
        <div class="d-flex rounded shadow-sm">
            <input type="text" class="form-control me-2" name="domain_subdomain" value="{{ $data['domain_subdomain'] ?? '' }}">
            <select class="form-control" name="subdomain">
                @foreach($subdomains as $subdomain)
                    <option value="{{ $subdomain->domain }}"{{ $data['subdomain'] ?? '' == $subdomain->domain ? ' selected' : '' }}>{{ $subdomain->domain }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="py-3 d-flex align-items-center text-uppercase text-muted small">
        <div class="flex-grow-1 border-top border-muted me-3"></div>
        {{ trans("global.or") }}
        <div class="flex-grow-1 border-top border-muted ms-3"></div>
    </div>

@endif
@include("shared/input", ['name' => 'domain', 'label' => __($subdomains->isNotEmpty() ? 'provisioning.admin.subdomains_hosts.use_owndomain' : 'provisioning.domain'), 'value' => $data->domain ?? ''])

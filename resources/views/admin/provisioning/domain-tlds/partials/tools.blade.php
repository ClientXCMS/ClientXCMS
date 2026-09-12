<div class="grid xl:grid-cols-2 gap-6 mb-6">
    <section class="card">
        <h2 class="text-xl font-semibold mb-4">{{ __('provisioning.admin.domain_tlds.tools.catalog') }}</h2>
        <form method="POST" action="{{ route('admin.domain_tlds.tools.catalog') }}" class="space-y-4" data-catalog-start>
            @csrf
            <label class="block">{{ __('provisioning.admin.domain_tlds.tools.registrar') }}
                <select class="input-text" name="registrar" required>
                    <option value="">-</option>
                    @foreach($registrars as $registrar)
                    <option value="{{ $registrar->uuid() }}" @disabled(!($registrar instanceof \App\Contracts\Domain\DomainCatalogInterface))>
                        {{ $registrar->title() }}{{ !($registrar instanceof \App\Contracts\Domain\DomainCatalogInterface) ? ' - '.__('provisioning.admin.domain_tlds.tools.unsupported') : '' }}
                    </option>
                    @endforeach
                </select>
            </label>
            <label class="block">{{ __('provisioning.admin.domain_tlds.server') }}
                <select class="input-text" name="server_id" required>
                    <option value="">-</option>
                    @foreach($servers as $server)
                    <option value="{{ $server->id }}" data-registrar="{{ $server->hostname }}">{{ $server->name }} - {{ $server->hasMetadata('test_mode') ? 'Sandbox' : 'Production' }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">{{ __('provisioning.admin.domain_tlds.tools.extensions') }}
                <textarea class="input-text min-h-24" name="extensions" required>{{ old('extensions', 'com, net, org, fr, eu, be, ch, de, es, it, nl, uk, io, co, info, biz, online, store, tech, dev, app') }}</textarea>
                <span class="block mt-1 text-sm text-gray-500">{{ __('provisioning.admin.domain_tlds.tools.extensions_help') }}</span>
            </label>
            <p class="text-sm text-gray-500">{{ __('provisioning.admin.domain_tlds.tools.worker_help') }}</p>
            <button class="btn btn-primary">{{ __('provisioning.admin.domain_tlds.tools.load') }}</button>
        </form>
    </section>

    <section class="card">
        <h2 class="text-xl font-semibold mb-4">{{ __('provisioning.admin.domain_tlds.tools.copy') }}</h2>
        <form method="POST" action="{{ route('admin.domain_tlds.tools.copy') }}" class="space-y-4">
            @csrf
            <label class="block">{{ __('provisioning.admin.domain_tlds.tools.source') }}
                <select class="input-text" name="source_id" required>
                    @foreach($tlds as $tld)<option value="{{ $tld->id }}">{{ $tld->extension }}</option>@endforeach
                </select>
            </label>
            <fieldset data-target-list>
                <legend>{{ __('provisioning.admin.domain_tlds.tools.destinations') }}</legend>
                <input class="input-text mb-2" data-filter-targets placeholder="{{ __('provisioning.admin.domain_tlds.tools.filter') }}">
                <div data-select-visible>
                    @include('admin/shared/checkbox', [
                    'name' => 'select_visible',
                    'label' => __('provisioning.admin.domain_tlds.tools.select_visible'),
                    'value' => '1',
                    'checked' => false,
                    ])
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2 max-h-64 overflow-auto mt-3">
                    @foreach($tlds as $tld)
                    <div data-target="{{ $tld->extension }}">
                        @include('admin/shared/checkbox', [
                        'name' => 'destinations[]',
                        'label' => $tld->extension,
                        'value' => $tld->id,
                        'checked' => in_array($tld->id, old('destinations', [])),
                        ])
                    </div>
                    @endforeach
                </div>
            </fieldset>
            <fieldset>
                <legend>{{ __('provisioning.admin.domain_tlds.tools.fields') }}</legend>
                <div class="grid sm:grid-cols-2 gap-3 mt-2">
                    @foreach([...\App\Services\Domain\DomainDefaultsService::FIELDS, 'prices'] as $field)
                    @include('admin/shared/checkbox', [
                    'name' => 'fields[]',
                    'label' => __('provisioning.admin.domain_tlds.tools.field_'.$field),
                    'value' => $field,
                    'checked' => in_array($field, old('fields', ['default_nameservers', 'default_dns_records', 'apply_default_dns', 'dns_management'])),
                    ])
                    @endforeach
                </div>
            </fieldset>
            <button class="btn btn-primary">{{ __('provisioning.admin.domain_tlds.tools.preview') }}</button>
        </form>
    </section>
</div>

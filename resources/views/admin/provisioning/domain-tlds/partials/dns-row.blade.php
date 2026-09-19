<div data-dns-row class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end rounded-xl border dark:border-gray-700 p-3 mb-3">
    <label class="text-sm">Type<select class="input-text" name="default_dns_records[{{ $index }}][type]">@foreach(['A','AAAA','CNAME','MX','TXT'] as $type)<option value="{{ $type }}" @selected(($record['type'] ?? 'A' )===$type)>{{ $type }}</option>@endforeach</select></label>
    <label class="text-sm">{{ __('global.name') }}<input class="input-text" name="default_dns_records[{{ $index }}][name]" value="{{ $record['name'] ?? '@' }}" required></label>
    <label class="text-sm md:col-span-2">{{ __('provisioning.admin.domain_tlds.tools.value') }}<input class="input-text" name="default_dns_records[{{ $index }}][value]" value="{{ $record['value'] ?? '' }}" required></label>
    <div class="grid grid-cols-2 gap-2">
        <label class="text-sm">TTL<input class="input-text" name="default_dns_records[{{ $index }}][ttl]" value="{{ $record['ttl'] ?? 3600 }}" type="number" min="60" required></label>
        <label class="text-sm">{{ __('provisioning.admin.domain_tlds.tools.priority') }}<input class="input-text" data-dns-priority name="default_dns_records[{{ $index }}][priority]" value="{{ $record['priority'] ?? '' }}" type="number" min="0" max="65535"></label>
    </div>
    <button type="button" class="btn btn-danger" data-remove-row>{{ __('global.delete') }}</button>
</div>
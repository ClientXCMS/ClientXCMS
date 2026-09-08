<div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-6">{{ __('provisioning.domain_manager.dns') }}</h2>
    <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
        <table class="w-full text-sm text-left [&_th]:px-4 [&_th]:py-3 [&_td]:px-4 [&_td]:py-4 [&_td]:align-top [&_td]:max-w-xs [&_td]:break-words">
            <thead class="bg-slate-50 text-slate-500 dark:bg-slate-900/50 dark:text-slate-400"><tr><th class="text-left">Type</th><th class="text-left">Name</th><th class="text-left">Value</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
            @forelse($records as $record)
                <tr>
                    <td>{{ $record['type'] ?? '' }}</td>
                    <td>{{ $record['name'] ?? '' }}</td>
                    <td>{{ $record['value'] ?? '' }}</td>
                    <td>
                        <form method="POST" action="{{ route('front.services.domains.dns.destroy', ['service' => $service, 'record' => $record['id'] ?? '']) }}">
                            @csrf
                            @method('DELETE')
                            <button class="inline-flex items-center justify-center rounded-lg bg-red-50 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-100 dark:bg-red-900/20">{{ __('global.delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-slate-500">{{ __('global.no_results') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <form method="POST" action="{{ route('front.services.domains.dns.store', ['service' => $service]) }}" class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
        @csrf
        <div>
        @include('shared/input', ['name' => 'type', 'label' => 'Type', 'value' => old('type', 'A')])
</div>
<div>
        @include('shared/input', ['name' => 'name', 'label' => 'Name', 'value' => old('name', '@')])
</div>
<div>
        @include('shared/input', ['name' => 'value', 'label' => 'Value', 'value' => old('value')])
</div>
<div>
        @include('shared/input', ['name' => 'ttl', 'label' => 'TTL', 'value' => old('ttl', 3600), 'type' => 'number'])
</div>
        <button class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 transition-colors sm:col-span-2 lg:col-span-4">{{ __('admin.create') }}</button>
    </form>
</div>

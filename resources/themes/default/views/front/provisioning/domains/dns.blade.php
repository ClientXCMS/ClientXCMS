<div class="card dark:text-gray-400">
    <h2 class="text-lg font-semibold dark:text-gray-300 mb-4">{{ __('provisioning.domain_manager.dns') }}</h2>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead><tr><th class="text-left">Type</th><th class="text-left">Name</th><th class="text-left">Value</th><th></th></tr></thead>
            <tbody>
            @foreach($records as $record)
                <tr>
                    <td>{{ $record['type'] ?? '' }}</td>
                    <td>{{ $record['name'] ?? '' }}</td>
                    <td>{{ $record['value'] ?? '' }}</td>
                    <td>
                        <form method="POST" action="{{ route('front.services.domains.dns.destroy', ['service' => $service, 'record' => $record['id'] ?? '']) }}">
                            @csrf
                            @method('DELETE')
                            <button class="btn-danger">{{ __('global.delete') }}</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <form method="POST" action="{{ route('front.services.domains.dns.store', ['service' => $service]) }}" class="grid md:grid-cols-4 gap-3 mt-4">
        @csrf
        @include('shared/input', ['name' => 'type', 'label' => 'Type', 'value' => old('type', 'A')])
        @include('shared/input', ['name' => 'name', 'label' => 'Name', 'value' => old('name', '@')])
        @include('shared/input', ['name' => 'value', 'label' => 'Value', 'value' => old('value')])
        @include('shared/input', ['name' => 'ttl', 'label' => 'TTL', 'value' => old('ttl', 3600), 'type' => 'number'])
        <button class="btn-primary md:col-span-4">{{ __('admin.create') }}</button>
    </form>
</div>

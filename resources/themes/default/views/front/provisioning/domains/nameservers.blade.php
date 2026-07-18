<div class="card dark:text-gray-400">
    <h2 class="text-lg font-semibold dark:text-gray-300 mb-4">{{ __('provisioning.domain_manager.nameservers') }}</h2>
    <form method="POST" action="{{ route('front.services.domains.nameservers', ['service' => $service]) }}">
        @csrf
        @for($i = 0; $i < max(2, count($nameservers)); $i++)
            @include('shared/input', ['name' => "nameservers[$i]", 'label' => __('provisioning.domain_manager.nameserver', ['number' => $i + 1]), 'value' => old("nameservers.$i", $nameservers[$i] ?? '')])
        @endfor
        <button class="btn-primary mt-3">{{ __('global.save') }}</button>
    </form>
</div>

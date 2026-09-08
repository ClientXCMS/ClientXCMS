<div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-6">{{ __('provisioning.domain_manager.nameservers') }}</h2>
    <form method="POST" action="{{ route('front.services.domains.nameservers', ['service' => $service]) }}">
        @csrf
        <div class="grid sm:grid-cols-2 gap-4">
        @for($i = 0; $i < max(2, count($nameservers)); $i++)
            @include('shared/input', ['name' => "nameservers[$i]", 'label' => __('provisioning.domain_manager.nameserver', ['number' => $i + 1]), 'value' => old("nameservers.$i", $nameservers[$i] ?? '')])
        @endfor
        </div>
        <button class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 transition-colors mt-3">{{ __('global.save') }}</button>
    </form>
</div>

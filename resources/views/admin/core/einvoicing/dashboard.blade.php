@extends('admin/layouts/admin')

@section('title', __('einvoicing.admin.dashboard.title'))

@section('content')
@php
$statusClass = static fn (string $status) => match ($status) {
'delivered', 'accepted' => 'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400',
'submitted', 'generated', 'closed' => 'bg-blue-100 text-blue-800 dark:bg-blue-800/30 dark:text-blue-400',
'pending', 'open' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-400',
'failed', 'rejected' => 'bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-400',
default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
};
$statusIcon = static fn (string $status) => match ($status) {
'delivered', 'accepted' => 'bi-check-circle-fill',
'failed', 'rejected' => 'bi-exclamation-circle-fill',
'submitted', 'generated', 'closed' => 'bi-send-check-fill',
default => 'bi-clock-fill',
};
@endphp

<div class="container mx-auto space-y-6">
    @include('admin/shared/alerts')

    <div class="card">
        <div class="card-heading gap-4">
            <div class="flex items-start gap-3">
                <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400"><i class="bi bi-file-earmark-code text-xl"></i></span>
                <div>
                    <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ __('einvoicing.admin.dashboard.title') }}</h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('einvoicing.admin.dashboard.description') }}</p>
                </div>
            </div>
            <a href="{{ route('admin.settings.show', ['billing', 'billing']) }}" class="btn btn-secondary text-sm"><i class="bi bi-gear"></i> {{ __('einvoicing.admin.dashboard.settings') }}</a>
        </div>
    </div>

    @if (setting('einvoicing_provider', 'local') === 'local')
    <div class="flex items-start gap-3 rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-900 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300" role="alert">
        <i class="bi bi-shield-exclamation mt-0.5 text-xl"></i>
        <div>
            <p class="font-semibold">{{ __('einvoicing.admin.dashboard.local_warning_title') }}</p>
            <p class="mt-0.5 text-sm">{{ __('einvoicing.admin.dashboard.local_warning_description') }}</p>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
        ['icon' => 'bi-diagram-3', 'label' => __('einvoicing.admin.dashboard.provider'), 'value' => strtoupper(setting('einvoicing_provider', 'local'))],
        ['icon' => 'bi-calendar3', 'label' => __('einvoicing.admin.dashboard.regime'), 'value' => __('einvoicing.settings.regimes.'.setting('einvoicing_vat_regime', 'real_normal_monthly'))],
        ['icon' => 'bi-globe2', 'label' => __('einvoicing.admin.dashboard.timezone'), 'value' => setting('einvoicing_timezone', 'Europe/Paris')],
        ['icon' => 'bi-power', 'label' => __('einvoicing.admin.dashboard.activation'), 'value' => setting('einvoicing_activation_date') ? \Carbon\Carbon::parse(setting('einvoicing_activation_date'))->isoFormat('LL') : __('einvoicing.admin.dashboard.disabled')],
        ] as $metric)
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center gap-3"><span class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300"><i class="bi {{ $metric['icon'] }}"></i></span>
                <div class="min-w-0">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $metric['label'] }}</p>
                    <p class="mt-0.5 truncate text-sm font-semibold text-gray-800 dark:text-gray-200" title="{{ $metric['value'] }}">{{ $metric['value'] }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @foreach ([
    ['key' => 'documents', 'items' => $documents, 'icon' => 'bi-files', 'columns' => 5],
    ['key' => 'periods', 'items' => $periods, 'icon' => 'bi-calendar-range', 'columns' => 5],
    ['key' => 'payments', 'items' => $payments, 'icon' => 'bi-cash-stack', 'columns' => 4],
    ] as $section)
    <div class="card p-0 overflow-hidden">
        <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
            <div class="flex items-center gap-3"><span class="inline-flex size-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400"><i class="bi {{ $section['icon'] }}"></i></span>
                <div>
                    <h2 class="font-semibold text-gray-800 dark:text-gray-200">{{ __('einvoicing.admin.dashboard.'.$section['key']) }}</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('einvoicing.admin.dashboard.'.$section['key'].'_description') }}</p>
                </div>
            </div><span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-300">{{ $section['items']->count() }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800/60">
                    <tr>
                        @php $headings = $section['key'] === 'documents' ? ['document', 'provider_column', 'status', 'error', 'actions'] : ($section['key'] === 'periods' ? ['type', 'period', 'deadline', 'status', 'actions'] : ['invoice', 'type', 'amount', 'date']); @endphp
                        @foreach ($headings as $heading)<th class="px-5 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400 {{ $heading === 'actions' ? 'text-end' : '' }}">{{ __('einvoicing.admin.dashboard.'.$heading) }}</th>@endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-slate-900">
                    @forelse ($section['items'] as $item)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/70">
                        @if ($section['key'] === 'documents')
                        <td class="px-5 py-3"><span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ optional($item->documentable)->identifier() ?: '—' }}</span>
                            <p class="mt-0.5 text-xs text-gray-500">{{ $item->format }}</p>
                        </td>
                        <td class="px-5 py-3"><span class="rounded-md bg-gray-100 px-2 py-1 text-xs font-medium uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">{{ $item->provider }}</span></td>
                        <td class="px-5 py-3"><span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass($item->status) }}"><i class="bi {{ $statusIcon($item->status) }}"></i>{{ __('einvoicing.document_statuses.'.$item->status) }}</span></td>
                        <td class="max-w-xs px-5 py-3"><span class="text-sm {{ $item->last_error_message ? 'text-red-600 dark:text-red-400' : 'text-gray-400' }}" title="{{ $item->last_error_message }}">{{ $item->last_error_message ? \Illuminate\Support\Str::limit($item->last_error_message, 90) : __('einvoicing.admin.dashboard.no_error') }}</span></td>
                        <td class="whitespace-nowrap px-5 py-3 text-end">
                            <form class="inline" method="post" action="{{ route('admin.electronic-invoicing.retry', ['document', $item]) }}">@csrf<button class="btn btn-secondary px-2.5 py-1.5 text-xs" title="{{ __('einvoicing.admin.dashboard.retry') }}"><i class="bi bi-arrow-repeat"></i></button></form>@if($item->structured_document_path) @foreach(['pdf' => 'download_pdf', 'xml' => 'download_xml', 'manifest' => 'download_manifest'] as $file => $label)<a class="btn btn-secondary px-2.5 py-1.5 text-xs" href="{{ route('admin.electronic-invoicing.download', ['document', $item, 'file' => $file]) }}">{{ __('einvoicing.admin.dashboard.'.$label) }}</a>@endforeach @endif
                        </td>
                        @elseif ($section['key'] === 'periods')
                        <td class="px-5 py-3 text-sm font-medium text-gray-800 dark:text-gray-200">{{ __('einvoicing.report_types.'.$item->type) }}</td>
                        <td class="whitespace-nowrap px-5 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $item->period_start->isoFormat('L') }} <i class="bi bi-arrow-right mx-1"></i> {{ $item->period_end->isoFormat('L') }}</td>
                        <td class="whitespace-nowrap px-5 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $item->due_at->isoFormat('LLL') }}</td>
                        <td class="px-5 py-3"><span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass($item->status) }}"><i class="bi {{ $statusIcon($item->status) }}"></i>{{ __('einvoicing.document_statuses.'.$item->status) }}</span></td>
                        <td class="whitespace-nowrap px-5 py-3 text-end">
                            <form class="inline" method="post" action="{{ route('admin.electronic-invoicing.retry', ['period', $item]) }}">@csrf<button class="btn btn-secondary px-2.5 py-1.5 text-xs"><i class="bi bi-arrow-repeat"></i> {{ __('einvoicing.admin.dashboard.retry') }}</button></form>@if($item->artifact_path)<a class="btn btn-secondary px-2.5 py-1.5 text-xs" href="{{ route('admin.electronic-invoicing.download', ['period', $item]) }}">{{ __('einvoicing.admin.dashboard.download_xml') }}</a><a class="btn btn-secondary px-2.5 py-1.5 text-xs" href="{{ route('admin.electronic-invoicing.download', ['period', $item, 'file' => 'manifest']) }}">{{ __('einvoicing.admin.dashboard.download_manifest') }}</a>@endif
                        </td>
                        @else
                        <td class="px-5 py-3 text-sm font-semibold text-gray-800 dark:text-gray-200">{{ optional($item->invoice)->identifier() ?: '—' }}</td>
                        <td class="px-5 py-3"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $item->type === 'refund' ? 'bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-400' : 'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400' }}">{{ __('einvoicing.payment_types.'.$item->type) }}</span></td>
                        <td class="whitespace-nowrap px-5 py-3 text-sm font-semibold {{ $item->type === 'refund' ? 'text-red-600 dark:text-red-400' : 'text-gray-800 dark:text-gray-200' }}">{{ $item->type === 'refund' ? '−' : '' }}{{ formatted_price($item->amount, $item->currency) }}</td>
                        <td class="whitespace-nowrap px-5 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $item->occurred_at->isoFormat('LLL') }}</td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $section['columns'] }}" class="px-6 py-10 text-center"><i class="bi bi-inbox text-3xl text-gray-300 dark:text-gray-600"></i>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('einvoicing.admin.dashboard.empty_'.$section['key']) }}</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($section['key'] === 'documents' && $documents->hasPages())<div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">{{ $documents->links('admin.shared.layouts.pagination') }}</div>@endif
    </div>
    @endforeach

    <div class="card p-0 overflow-hidden">
        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
            <div class="flex items-center gap-3"><span class="inline-flex size-9 items-center justify-center rounded-lg bg-violet-50 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400"><i class="bi bi-journal-check"></i></span>
                <div>
                    <h2 class="font-semibold text-gray-800 dark:text-gray-200">{{ __('einvoicing.admin.dashboard.accounting_exports') }}</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('einvoicing.admin.dashboard.accounting_exports_description') }}</p>
                </div>
            </div><span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold dark:bg-gray-700">{{ $accountingExports->count() }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800/60">
                    <tr>@foreach(['document', 'provider_column', 'event', 'status', 'external_id', 'error', 'actions'] as $heading)<th class="px-5 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">{{ __('einvoicing.admin.dashboard.'.$heading) }}</th>@endforeach</tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-slate-900">
                    @forelse($accountingExports as $export)
                    @php $source = $export->exportable; $invoice = $source instanceof \App\Models\Billing\PaymentTransaction ? $source->invoice : ($source instanceof \App\Models\Billing\CreditNote ? $source->invoice : $source); @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/70">
                        <td class="px-5 py-3 text-sm font-semibold text-gray-800 dark:text-gray-200">{{ method_exists($source, 'identifier') ? $source->identifier() : optional($invoice)->identifier() }}</td>
                        <td class="px-5 py-3"><span class="rounded-md bg-violet-100 px-2 py-1 text-xs font-medium uppercase text-violet-700 dark:bg-violet-900/30 dark:text-violet-300">{{ $export->provider }}</span>
                            <p class="mt-1 text-xs text-gray-500">{{ __('einvoicing.admin.dashboard.accounting_only') }}</p>
                        </td>
                        <td class="px-5 py-3 text-sm text-gray-600 dark:text-gray-400">{{ __('einvoicing.accounting_events.'.$export->event_type) }}</td>
                        <td class="px-5 py-3"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass($export->status) }}">{{ __('einvoicing.document_statuses.'.$export->status) }}</span></td>
                        <td class="max-w-xs px-5 py-3 text-xs text-gray-500 break-all">{{ $export->external_id ?: '—' }}</td>
                        <td class="max-w-xs px-5 py-3 text-sm text-red-600 dark:text-red-400">{{ $export->last_error_message ? \Illuminate\Support\Str::limit($export->last_error_message, 90) : '—' }}</td>
                        <td class="px-5 py-3 text-end">
                            <form method="post" action="{{ route('admin.electronic-invoicing.retry', ['accounting', $export]) }}">@csrf<button class="btn btn-secondary px-2.5 py-1.5 text-xs"><i class="bi bi-arrow-repeat"></i></button></form>
                        </td>
                    </tr>
                    @empty<tr>
                        <td colspan="7" class="px-6 py-10 text-center"><i class="bi bi-inbox text-3xl text-gray-300"></i>
                            <p class="mt-2 text-sm text-gray-500">{{ __('einvoicing.admin.dashboard.empty_accounting_exports') }}</p>
                        </td>
                    </tr>@endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
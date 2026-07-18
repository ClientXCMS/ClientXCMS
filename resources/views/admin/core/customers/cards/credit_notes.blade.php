<div class="card">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
            {{ __('admin.credit_notes.title') }}
        </h2>
        @if (staff_has_permission('admin.manage_invoices'))
            <button type="button" class="btn btn-primary inline-flex items-center gap-2" data-hs-overlay="#credit-note-overlay">
                <i class="bi bi-plus-lg"></i>
                {{ __('admin.credit_notes.issue_credit_note') }}
            </button>
        @endif
    </div>
    @if (staff_has_permission('admin.manage_invoices'))
        <div class="mb-3 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:border-amber-800/60 dark:bg-amber-900/20 dark:text-amber-300">
            <i class="bi bi-info-circle mt-0.5 shrink-0"></i>
            <span>{{ __('admin.credit_notes.delete_balance_warning') }}</span>
        </div>
    @endif
    <div class="border rounded-lg overflow-hidden dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
                <tr>
                    <th scope="col" class="px-6 py-3 text-start">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                            {{ __('client.invoices.credit_note_number') }}
                        </span>
                    </th>
                    <th scope="col" class="px-6 py-3 text-start">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                            {{ __('admin.credit_notes.original_invoice') }}
                        </span>
                    </th>
                    <th scope="col" class="px-6 py-3 text-start">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                            {{ __('store.total') }}
                        </span>
                    </th>
                    <th scope="col" class="px-6 py-3 text-start">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                            {{ __('global.date') }}
                        </span>
                    </th>
                    <th scope="col" class="px-6 py-3 text-end">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                            {{ __('global.actions') }}
                        </span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @if (count($creditNotes) == 0)
                    <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                        <td colspan="5" class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="flex flex-auto flex-col justify-center items-center p-2 md:p-3">
                                <p class="text-sm text-gray-800 dark:text-gray-400">
                                    {{ __('global.no_results') }}
                                </p>
                            </div>
                        </td>
                    </tr>
                @endif
                @foreach($creditNotes as $creditNote)
                    <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                        <td class="h-px w-px whitespace-nowrap">
                            <span class="block px-6 py-2">
                                <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                                    {{ $creditNote->credit_note_number }}
                                </span>
                            </span>
                        </td>
                        <td class="h-px w-px whitespace-nowrap">
                            <span class="block px-6 py-2">
                                @if ($creditNote->invoice)
                                    <a class="text-sm text-blue-600 dark:text-blue-500 hover:underline" href="{{ route('admin.invoices.show', ['invoice' => $creditNote->invoice]) }}">
                                        #{{ $creditNote->invoice->invoice_number }}
                                    </a>
                                @else
                                    <span class="text-sm text-gray-400">-</span>
                                @endif
                            </span>
                        </td>
                        <td class="h-px w-px whitespace-nowrap">
                            <span class="block px-6 py-2">
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ formatted_price($creditNote->amount + $creditNote->tax, $creditNote->currency) }}
                                </span>
                            </span>
                        </td>
                        <td class="h-px w-px whitespace-nowrap">
                            <span class="block px-6 py-2">
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $creditNote->created_at->format('d/m/Y') }}
                                </span>
                            </span>
                        </td>
                        <td class="h-px w-px whitespace-nowrap text-end px-6 py-2">
                            <div class="flex items-center justify-end gap-x-2">
                                <a href="{{ route('admin.credit_notes.pdf', $creditNote) }}" target="_blank" class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-white text-gray-700 shadow-sm align-middle hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-slate-900 dark:hover:bg-slate-800 dark:border-gray-700 dark:text-gray-400 dark:hover:text-white dark:focus:ring-offset-gray-800">
                                    <i class="bi bi-eye-fill"></i>
                                    {{ __('global.show') }}
                                </a>
                                <a href="{{ route('admin.credit_notes.download', $creditNote) }}" class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-white text-gray-700 shadow-sm align-middle hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-slate-900 dark:hover:bg-slate-800 dark:border-gray-700 dark:text-gray-400 dark:hover:text-white dark:focus:ring-offset-gray-800">
                                    <i class="bi bi-download"></i>
                                    {{ __('global.download') }}
                                </a>
                                @if (staff_has_permission('admin.manage_invoices'))
                                    <form action="{{ route('admin.credit_notes.destroy', $creditNote) }}" method="POST" class="inline confirmation-popup">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-red text-red-700 shadow-sm align-middle hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-red-900 dark:hover:bg-red-800 dark:border-red-700 dark:text-white dark:hover:text-white dark:focus:ring-offset-gray-800">
                                            <i class="bi bi-trash"></i>
                                            {{ __('global.delete') }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="py-1 px-4 mx-auto">
        {{ $creditNotes->links('admin.shared.layouts.pagination') }}
    </div>
</div>

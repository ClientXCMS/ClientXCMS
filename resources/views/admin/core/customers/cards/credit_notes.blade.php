<div class="card">
    <div class="flex justify-between">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-3">
            {{ __('admin.credit_notes.credit_notes') }}
        </h2>
    </div>
    <div class="border rounded-lg overflow-hidden dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
                <tr>
                    <th scope="col" class="px-6 py-3 text-start">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                            {{ __('admin.credit_notes.credit_note_number') }}
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
                            {{ __('admin.credit_notes.date') }}
                        </span>
                    </th>
                    <th scope="col" class="px-6 py-3 text-end">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                            {{ __('admin.credit_notes.actions') }}
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
                                <a href="{{ route('admin.credit_notes.pdf', $creditNote) }}" target="_blank" class="py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 dark:bg-slate-900 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800">
                                    <i class="bi bi-eye"></i> {{ __('global.view') }}
                                </a>
                                <a href="{{ route('admin.credit_notes.download', $creditNote) }}" class="py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 dark:bg-slate-900 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800">
                                    <i class="bi bi-download"></i> {{ __('global.download') }}
                                </a>
                                @if (staff_has_permission('admin.manage_invoices'))
                                    <form action="{{ route('admin.credit_notes.destroy', $creditNote) }}" method="POST" onsubmit="return confirm('{{ __('global.confirm_delete') }}')" class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="py-1 px-2 inline-flex items-center gap-x-1 text-xs font-medium rounded-lg border border-red-200 bg-red-50 text-red-800 shadow-sm hover:bg-red-100 dark:bg-red-950/20 dark:border-red-900/50 dark:text-red-400 dark:hover:bg-red-950/50">
                                            <i class="bi bi-trash"></i> {{ __('global.delete') }}
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

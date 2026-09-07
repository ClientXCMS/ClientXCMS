@extends('admin/settings/sidebar')
@section('title', __($translatePrefix .'.title'))
@section('setting')
<div class="container mx-auto">
    <div class="card">
        <div class="card-heading">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ __($translatePrefix . '.title') }}</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __($translatePrefix . '.subheading') }}</p>
            </div>
            <a class="btn btn-primary" href="{{ route($routePath . '.create') }}">{{ __('admin.create') }}</a>
        </div>
        <div class="border rounded-lg overflow-x-auto dark:border-gray-700" tabindex="0">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-start">
                            <div class="flex items-center gap-x-2">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                    #
                                </span>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-start">

                            <div class="flex items-center gap-x-2">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                    {{ __($translatePrefix . '.extension') }}
                                </span>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-start">

                            <div class="flex items-center gap-x-2">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                    {{ __('global.status')}}
                                </span>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-start">
                            <div class="flex items-center gap-x-2">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">
                                    {{ __('global.actions')}}
                                </span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($items as $item)
                    <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                        <td class="h-px w-px whitespace-nowrap">
                            <span class="block px-6 py-2">
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item->id }}</span>
                            </span>
                        </td>
                        <td class="px-6 py-2 text-sm text-gray-600 dark:text-gray-400">{{ $item->extension }}</td>
                        <td class="px-6 py-2 text-sm text-gray-600 dark:text-gray-400">
                                            <x-badge-state state="{{ $item->status }}"></x-badge-state>

                        </td>

                        <td class="h-px w-px whitespace-nowrap">

                            <a href="{{ route($routePath . '.show', ['domain_tld' => $item]) }}">
                                <span class="px-1 py-1.5">
                                    <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-white text-gray-700 shadow-sm align-middle hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-slate-900 dark:hover:bg-slate-800 dark:border-gray-700 dark:text-gray-400 dark:hover:text-white dark:focus:ring-offset-gray-800">
                                        <i class="bi bi-eye-fill"></i>
                                        {{ __('global.show') }}
                                    </span>
                                </span>
                            </a>
                            <form method="POST" action="{{ route($routePath . '.show', ['domain_tld' => $item]) }}" class="inline confirmation-popup">
                                @method('DELETE')
                                @csrf
                                <button>
                                    <span class="py-1 px-2 inline-flex justify-center items-center gap-2 rounded-lg border font-medium bg-red text-red-700 shadow-sm align-middle hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-red-900 dark:hover:bg-red-800 dark:border-red-700 dark:text-white dark:hover:text-white dark:focus:ring-offset-gray-800">
                                        <i class="bi bi-trash"></i>

                                        {{ __('global.delete') }}
                                    </span>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center">{{ __('global.no_results') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="py-1 px-4 mx-auto">{{ $items->links('admin.shared.layouts.pagination') }}</div>
    </div>
</div>
@endsection
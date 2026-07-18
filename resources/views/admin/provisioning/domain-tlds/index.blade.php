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
        <div class="border rounded-lg overflow-hidden dark:border-gray-700">
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
                        <td class="px-6 py-2 text-sm text-gray-600 dark:text-gray-400">{{ $item->id }}</td>
                        <td class="px-6 py-2 text-sm text-gray-600 dark:text-gray-400">{{ $item->extension }}</td>
                        <td class="px-6 py-2 text-sm text-gray-600 dark:text-gray-400">{{ $item->status }}</td>
                        <td class="px-6 py-2 whitespace-nowrap">
                            <a class="btn btn-secondary" href="{{ route($routePath . '.show', ['domain_tld' => $item]) }}">{{ __('global.show') }}</a>
                            <form method="POST" action="{{ route($routePath . '.destroy', ['domain_tld' => $item]) }}" class="inline confirmation-popup">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger">{{ __('global.delete') }}</button>
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
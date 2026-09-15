@extends('admin/layouts/admin')

@section('title', __('admin.queues.title'))

@section('content')
<div class="container mx-auto">
    @include('admin/shared/alerts')

    @if (! $summary['manageable'])
    <div class="card text-yellow-600" role="alert">
        {{ $summary['synchronous'] ? __('admin.queues.sync_driver') : __('admin.queues.unsupported_driver', ['driver' => $health->connection()]) }}
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-5 gap-4 mb-6">
        @foreach (['waiting', 'delayed', 'reserved', 'failed'] as $metric)
        <a href="{{ route('admin.queues.index', ['status' => $metric]) }}"
            class="inline-flex items-center justify-between gap-x-3 py-3 px-4 text-sm font-medium bg-white border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700 {{ $status === $metric ? 'text-primary ring-1 ring-primary' : 'text-gray-800 dark:text-white' }}">
            <span class="inline-flex items-center gap-x-2">
                <i class="bi {{ match($metric) { 'waiting' => 'bi-hourglass-split', 'delayed' => 'bi-clock-history', 'reserved' => 'bi-lock', 'failed' => 'bi-exclamation-triangle', default => 'bi-stack' } }}"></i>
                {{ __('admin.queues.statuses.'.$metric) }}
            </span>
            <span class="py-0.5 px-2 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">{{ $summary[$metric] }}</span>
        </a>
        @endforeach

        <div class="inline-flex items-center justify-between gap-x-3 py-3 px-4 text-sm font-medium bg-white border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700">
            <span class="inline-flex items-center gap-x-2 text-gray-800 dark:text-white">
                <i class="bi bi-cpu"></i> {{ __('admin.queues.worker') }}
            </span>
            <span class="inline-flex items-center gap-x-1.5 {{ $summary['heartbeat_stale'] ? 'text-red-600 dark:text-red-500' : 'text-green-600 dark:text-green-500' }}">
                <span class="size-2 inline-block rounded-full {{ $summary['heartbeat_stale'] ? 'bg-red-600' : 'bg-green-600' }}"></span>
                {{ $summary['heartbeat_stale'] ? __('admin.queues.inactive') : __('admin.queues.active') }}
            </span>
        </div>
    </div>

    <div class="flex flex-col">
        <div class="-m-1.5 overflow-x-auto">
            <div class="p-1.5 min-w-full inline-block align-middle">
                <div class="card">
                    <div class="card-heading">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                {{ __('admin.queues.title') }} - {{ __('admin.queues.statuses.'.$status) }}
                            </h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin.queues.description') }}</p>
                        </div>
                        <a href="{{ route('admin.queues.index', ['status' => $status]) }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-clockwise"></i> {{ __('admin.queues.refresh') }}
                        </a>
                    </div>

                    <form method="POST" action="{{ route('admin.queues.action') }}"
                        onsubmit="return confirm(@js(__('admin.queues.confirm_action')))" class="space-y-4">
                        @csrf
                        <div class="flex flex-wrap gap-2">
                            @if ($status === 'failed')
                            <button name="action" value="retry_failed" class="btn btn-primary"><i class="bi bi-arrow-repeat"></i> {{ __('admin.queues.actions.retry') }}</button>
                            <button name="action" value="delete_failed" class="btn btn-danger"><i class="bi bi-trash"></i> {{ __('admin.queues.actions.delete') }}</button>
                            @elseif ($status === 'reserved')
                            <button name="action" value="release" class="btn btn-primary"><i class="bi bi-unlock"></i> {{ __('admin.queues.actions.release') }}</button>
                            <button name="action" value="delete_pending" class="btn btn-danger"><i class="bi bi-trash"></i> {{ __('admin.queues.actions.delete') }}</button>
                            @else
                            <button name="action" value="make_available" class="btn btn-primary"><i class="bi bi-play-fill"></i> {{ __('admin.queues.actions.make_available') }}</button>
                            <button name="action" value="delete_pending" class="btn btn-danger"><i class="bi bi-trash"></i> {{ __('admin.queues.actions.delete') }}</button>
                            @endif
                        </div>

                        <div class="border rounded-lg overflow-hidden dark:border-gray-700">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-start">
                                            <input type="checkbox" class="shrink-0 border-gray-300 rounded text-blue-600 focus:ring-blue-500 dark:bg-slate-900 dark:border-gray-600"
                                                onclick="document.querySelectorAll('[name=\'ids[]\']').forEach(el => el.checked = this.checked)">
                                        </th>
                                        @foreach ([__('global.id'), __('admin.queues.job'), __('admin.queues.queue'), __('admin.queues.attempts'), __('admin.queues.date')] as $heading)
                                        <th scope="col" class="px-6 py-3 text-start">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">{{ $heading }}</span>
                                        </th>
                                        @endforeach
                                        @if ($status === 'failed')
                                        <th scope="col" class="px-6 py-3 text-start">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-gray-200">{{ __('admin.queues.error') }}</span>
                                        </th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @forelse ($jobs as $job)
                                    <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                                        <td class="h-px w-px whitespace-nowrap px-6 py-3">
                                            <input type="checkbox" name="ids[]" class="shrink-0 border-gray-300 rounded text-blue-600 focus:ring-blue-500 dark:bg-slate-900 dark:border-gray-600"
                                                value="{{ $status === 'failed' ? $job->uuid : $job->id }}">
                                        </td>
                                        <td class="h-px w-px px-6 py-3"><span class="text-sm text-gray-600 dark:text-gray-400 break-all">{{ $status === 'failed' ? $job->uuid : $job->id }}</span></td>
                                        <td class="h-px px-6 py-3"><span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $health->displayName($job->payload) }}</span></td>
                                        <td class="h-px w-px whitespace-nowrap px-6 py-3"><span class="py-1 px-2 inline-flex text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">{{ $job->queue }}</span></td>
                                        <td class="h-px w-px whitespace-nowrap px-6 py-3"><span class="text-sm text-gray-600 dark:text-gray-400">{{ $status === 'failed' ? '-' : $job->attempts }}</span></td>
                                        <td class="h-px w-px whitespace-nowrap px-6 py-3">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $status === 'failed' ? $job->failed_at?->isoFormat('LLL') : \Carbon\Carbon::createFromTimestamp($job->created_at)->isoFormat('LLL') }}</span>
                                        </td>
                                        @if ($status === 'failed')
                                        <td class="h-px px-6 py-3 max-w-md"><span class="text-sm text-red-600 dark:text-red-500 break-words">{{ \Illuminate\Support\Str::limit(str($job->exception)->before("\n"), 250) }}</span></td>
                                        @endif
                                    </tr>
                                    @empty
                                    <tr class="bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                                        <td colspan="{{ $status === 'failed' ? 7 : 6 }}" class="px-6 py-4 whitespace-nowrap text-center">
                                            <div class="flex flex-auto flex-col justify-center items-center p-2 md:p-3">
                                                <i class="bi bi-inbox text-2xl text-gray-400 mb-2"></i>
                                                <p class="text-sm text-gray-800 dark:text-gray-400">{{ __('admin.queues.empty') }}</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="py-1 px-4 mx-auto">{{ $jobs->links('admin.shared.layouts.pagination') }}</div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
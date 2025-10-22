<div class="flex items-start justify-between mb-3">
    <div>
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
            {{ __($translatePrefix . '.show.logs.title') }}
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __($translatePrefix . '.show.logs.subheading') }}
        </p>
    </div>
    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
        {{ trans_choice($translatePrefix . '.show.logs.count', $logs->count(), ['count' => $logs->count()]) }}
    </span>
</div>

@if ($logs->isEmpty())
    <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-700 p-6 text-center text-sm text-gray-500 dark:text-gray-400">
        {{ __($translatePrefix . '.show.logs.empty') }}
    </div>
@else
    <div class="hidden md:block overflow-hidden border border-gray-200 dark:border-gray-700 rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __($translatePrefix . '.show.logs.columns.timestamp') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __($translatePrefix . '.show.logs.columns.event') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __($translatePrefix . '.show.logs.columns.actor') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __($translatePrefix . '.show.logs.columns.details') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-slate-900">
                @foreach ($logs as $log)
                    @php
                        $actorName = $log->staff?->username ?? $log->customer?->full_name ?? __($translatePrefix . '.show.logs.actors.system');
                        $contextItems = collect($log->context ?? [])->filter(function ($value) {
                            return !is_array($value) ? filled($value) : !empty($value);
                        });
                    @endphp
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-200">
                            {{ $log->created_at?->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-200">
                            {{ __($translatePrefix . '.show.logs.statuses.' . $log->status) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-200">
                            {{ $actorName }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-200">
                            @if ($contextItems->isEmpty())
                                <span class="text-gray-400">{{ __($translatePrefix . '.show.logs.no_context') }}</span>
                            @else
                                <dl class="space-y-1">
                                    @foreach ($contextItems as $key => $value)
                                        <div class="flex gap-2">
                                            <dt class="text-gray-500 dark:text-gray-400 font-medium">{{ \Illuminate\Support\Str::headline($key) }}:</dt>
                                            <dd class="text-gray-800 dark:text-gray-200">
                                                {{ is_array($value) ? json_encode($value) : $value }}
                                            </dd>
                                        </div>
                                    @endforeach
                                </dl>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="md:hidden space-y-3">
        @foreach ($logs as $log)
            @php
                $actorName = $log->staff?->username ?? $log->customer?->full_name ?? __($translatePrefix . '.show.logs.actors.system');
                $contextItems = collect($log->context ?? [])->filter(function ($value) {
                    return !is_array($value) ? filled($value) : !empty($value);
                });
            @endphp
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 bg-white dark:bg-slate-900 space-y-2">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $log->created_at?->format('d/m/Y H:i') }}</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __($translatePrefix . '.show.logs.statuses.' . $log->status) }}</p>
                </div>
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    <span class="font-medium">{{ __($translatePrefix . '.show.logs.columns.actor') }}:</span> {{ $actorName }}
                </div>
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    <span class="font-medium">{{ __($translatePrefix . '.show.logs.columns.details') }}:</span>
                    @if ($contextItems->isEmpty())
                        <span class="text-gray-400">{{ __($translatePrefix . '.show.logs.no_context') }}</span>
                    @else
                        <ul class="mt-1 space-y-1">
                            @foreach ($contextItems as $key => $value)
                                <li>
                                    <span class="text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Str::headline($key) }}:</span>
                                    <span class="text-gray-800 dark:text-gray-200">{{ is_array($value) ? json_encode($value) : $value }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif

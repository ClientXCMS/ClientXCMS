@if (session('show_admin_layout_prompt') && auth('admin')->user()->admin_layout_prompted_at === null)
    <div class="fixed inset-0 z-[90] flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="admin-layout-prompt-title">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-800">
            <div class="flex size-12 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300">
                <i class="bi bi-layout-sidebar-inset text-xl"></i>
            </div>
            <h2 id="admin-layout-prompt-title" class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">
                {{ __('admin.admins.layout.prompt.title') }}
            </h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                {{ __('admin.admins.layout.prompt.description') }}
            </p>

            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                @foreach ([
                    \App\Models\Admin\Admin::LAYOUT_VERTICAL => ['icon' => 'bi-layout-sidebar-inset', 'label' => __('admin.admins.layout.vertical')],
                    \App\Models\Admin\Admin::LAYOUT_HORIZONTAL => ['icon' => 'bi-layout-text-sidebar-reverse', 'label' => __('admin.admins.layout.horizontal')],
                ] as $layout => $option)
                    <form method="POST" action="{{ route('admin.profile.layout.choose') }}">
                        @csrf
                        <input type="hidden" name="admin_layout" value="{{ $layout }}">
                        <button type="submit" class="flex w-full items-center gap-3 rounded-xl border border-gray-200 p-4 text-start text-sm font-medium text-gray-800 transition hover:border-indigo-400 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-700 dark:text-gray-100 dark:hover:border-indigo-500 dark:hover:bg-slate-700">
                            <i class="bi {{ $option['icon'] }} text-lg text-indigo-600 dark:text-indigo-300"></i>
                            <span>{{ $option['label'] }}</span>
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    </div>
@endif

<?php
/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2025
 */
?>


                        <div class="py-1 px-4 flex justify-between">
                            <div class="flex">
                                <span class="text-sm text-gray-600 dark:text-gray-400 items-center flex">
                                    {{ __('global.withselected') }}
                                </span>
                                <div class="flex gap-x-2">
                                    <select id="mass_action_select" class="ml-1 py-2 px-3 block border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400 dark:focus:ring-gray-600">
                                        <option value="action">{{ __('global.actions') }}</option>
                                        @foreach ($mass_actions as $mass_action)
                                            <option value="{{ $mass_action->action }}"{!! $mass_action->question ? ' data-question="' . $mass_action->question.'"' : '' !!}>{{ $mass_action->translate }}</option>
                                            @endforeach
                                    </select>
                                </div>
                            </div>
                            {{ $items->links('admin.shared.layouts.pagination') }}
                            <div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ __('global.showing') }} {{ $items->firstItem() }} - {{ $items->lastItem() }} {{ __('global.of') }} {{ $items->total() }} {{ __('global.results') }}
                                </span>
                            </div>
                        </div>

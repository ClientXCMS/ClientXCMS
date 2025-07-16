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
 * Year: 2025
 */
?>
?>
?>

<form method="POST" action="{{ route('register') }}">
    @csrf
    <div class="space-y-12">
        <div class="pb-6">
            <div class="border-b border-gray-900/10 pb-6">
                <div class="mt-5 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-6">
                    <div class="sm:col-span-3">
                        @include("shared.input", ["name" => "firstname", "label" => __('global.firstname') ])
                    </div>

                    <div class="sm:col-span-3">
                        @include("shared.input", ["name" => "lastname", "label" => __('global.lastname')])
                    </div>

                    <div class="sm:col-span-3">
                        @include("shared.input", ["name" => "email", "label" => __('global.email'), "type" => "email"])
                    </div>


                    <div class="sm:col-span-3">
                        @include("shared.input", ["name" => "phone", "label" => __('global.phone')])
                    </div>

                    <div class="sm:col-span-3">
                        @include("shared.input", ["name" => "address", "label" => __('global.address')])
                    </div>
                    <div class="sm:col-span-2">
                        @include("shared.input", ["name" => "address2", "label" => __('global.address2')])
                    </div>

                    <div class="sm:col-span-1">
                        @include("shared.input", ["name" => "zipcode", "label" => __('global.zip')])
                    </div>



                    <div class="sm:col-span-2">
                        @include("shared.select", ["name" => "country", "label" => __('global.country'), "options" => $countries, "value" => old("country", "FR")])
                    </div>

                    <div class="sm:col-span-2">
                        @include("shared.input", ["name" => "region", "label" => __('global.region')])
                    </div>
                    <div class="sm:col-span-2">
                        @include("shared.input", ["name" => "city", "label" => __('global.city')])
                    </div>

                    <div class="sm:col-span-3">
                        @include("shared.password", ["name" => "password", "label" => __('global.password'), "generate" => true])
                    </div>

                    <div class="sm:col-span-3">
                        @include("shared.password", ["name" => "password_confirmation", "label" => __('global.password_confirmation')])
                    </div>
                    @if (setting('register.toslink'))
                        <div class="sm:col-span-3 flex gap-x-6 mb-2">
                            @include('shared/checkbox', ['label' => __('auth.register.accept'), 'name' => 'accept_tos'])
                        </div>
                    @endif

                    @if (isset($redirect))
                        <input type="hidden" name="redirect" value="{{ $redirect }}">
                    @endif
                    @include('shared.captcha')

                </div>
            </div>
            <button class="btn-primary block w-full">
                {{ __('auth.register.btn') }}
            </button>
        </div>
    </div>
</form>

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
    <div class="mb-4">
        <div class="pb-3">
            <div class="pb-3">
                <div class="row g-3">
                    <div class="col-md-6">
                        @include("shared.input", ["name" => "firstname", "label" => __('global.firstname') ])
                    </div>

                    <div class="col-md-6">
                        @include("shared.input", ["name" => "lastname", "label" => __('global.lastname')])
                    </div>

                    <div class="col-md-6">
                        @include("shared.input", ["name" => "email", "label" => __('global.email'), "type" => "email"])
                    </div>

                    <div class="col-md-6">
                        @include("shared.input", ["name" => "phone", "label" => __('global.phone')])
                    </div>

                    <div class="col-md-4">
                        @include("shared.input", ["name" => "address", "label" => __('global.address')])
                    </div>
                    <div class="col-md-4">
                        @include("shared.input", ["name" => "address2", "label" => __('global.address2')])
                    </div>

                    <div class="col-md-4">
                        @include("shared.input", ["name" => "zipcode", "label" => __('global.zip')])
                    </div>

                    <div class="col-md-4">
                        @include("shared.select", ["name" => "country", "label" => __('global.country'), "options" => $countries, "value" => old("country", "FR")])
                    </div>

                    <div class="col-md-4">
                        @include("shared.input", ["name" => "region", "label" => __('global.region')])
                    </div>

                    <div class="col-md-4">
                        @include("shared.input", ["name" => "city", "label" => __('global.city')])
                    </div>

                    <div class="col-md-6">
                        @include("shared.password", ["name" => "password", "label" => __('global.password'), "generate" => true])
                    </div>

                    <div class="col-md-6">
                        @include("shared.password", ["name" => "password_confirmation", "label" => __('global.password_confirmation')])
                    </div>

                    @if (setting('register.toslink'))
                        <div class="col-12">
                            <div class="form-check">
                                @include('shared/checkbox', ['label' => __('auth.register.accept'), 'name' => 'accept_tos'])
                            </div>
                        </div>
                    @endif

                    @if (isset($redirect))
                        <input type="hidden" name="redirect" value="{{ $redirect }}">
                    @endif
                    @include('shared.captcha')
                </div>
            </div>
            <button class="btn btn-primary w-100 mt-3">
                {{ __('auth.register.btn') }}
            </button>
        </div>
    </div>
</form>

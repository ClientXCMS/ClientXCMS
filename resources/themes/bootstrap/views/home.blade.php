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
@extends('layouts/front')
@section('title', setting('theme_home_title_meta', setting('app.name')))
@section('content')

    <header class="py-5">
        <div class="container px-5">
            <div class="row gx-5 align-items-center justify-content-center">
                <div class="col-lg-8 col-xl-7 col-xxl-6">
                    <div class="my-5 text-center text-xl-start">
                        <h1 class="display-5 fw-bolder text-secondary mb-2">{{ translated_setting('theme_home_title', setting('theme_home_title', setting('app.name'))) }}</h1>
                        <p class="lead fw-normal text-secondary-emphasis mb-4">{{ translated_setting('theme_home_subtitle', setting('theme_home_subtitle', 'Hébergeur français de qualité utilisant la nouvelle version Next GEN')) }}</p>
                    </div>
                </div>
                <div class="col-xl-5 col-xxl-6 d-none d-xl-block text-center"><img class="img-fluid rounded-3 my-5" src="{{ setting('theme_home_image') }}" alt="" /></div>
            </div>
        </div>
    </header>
    {!! render_theme_sections() !!}
    @endsection

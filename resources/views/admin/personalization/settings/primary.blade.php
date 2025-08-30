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

@extends('admin.settings.sidebar')
@section('title', __('personalization.primary.title'))
@section('setting')
    <div class="card">
        <h4 class="font-semibold uppercase text-gray-600 dark:text-gray-400">
            {{ __('personalization.primary.title') }}
        </h4>
        <p class="mb-2 font-semibold text-gray-600 dark:text-gray-400">
            {{ __('personalization.primary.description') }}
        </p>

        <form action="{{ route('admin.personalization.primary') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    @include('admin/shared/input', ['type' => 'color','name' => 'theme_primary', 'label' => __('personalization.primary.fields.primary_color'), 'value' => $primary_color])
                </div>

                <div>
                    @include('admin/shared/input', ['type' => 'color', 'name' => 'theme_secondary', 'label' => __('personalization.primary.fields.secondary_color'), 'value' => $secondary_color])
                </div>
                    @method('PUT')
            </div>
            <p class="text-gray-500 mt-2">Pour les personnes en auto-hébergement, veuillez exécuter la commande npm run build après avoir effectué vos modifications. Pour les offres cloud, la mise à jour sera appliquée automatiquement une minute après la sauvegarde.</p>
            <button type="submit" class="btn btn-primary mt-2">{{ __('global.save') }}</button>


        </form>
@endsection

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

@extends('layouts.front')
@section('title', $section->formattedName())
@section('styles')
    {{-- Links would otherwise navigate the preview iframe away from the section. --}}
    <base target="_blank">
    <style>
        /* The admin page sizes the iframe to the section, so no inner scrollbar. */
        html, body {
            height: auto !important;
            min-height: 0 !important;
            overflow: hidden !important;
        }
    </style>
@endsection
@section('content')
    <div id="section-preview">
        {!! $html !!}
    </div>
    <script>
        // Themes wrap content in their own chrome (nav, header, footer, toasts) with
        // any markup: every element outside the path from <body> to the section is hidden.
        document.addEventListener('DOMContentLoaded', function () {
            let node = document.getElementById('section-preview');
            while (node && node !== document.body) {
                const parent = node.parentElement;
                Array.from(parent.children).forEach(function (sibling) {
                    if (sibling !== node && !['SCRIPT', 'STYLE', 'LINK'].includes(sibling.tagName)) {
                        sibling.style.setProperty('display', 'none', 'important');
                    }
                });
                if (parent !== document.body) {
                    ['padding', 'margin', 'min-height'].forEach(function (property) {
                        parent.style.setProperty(property, '0', 'important');
                    });
                }
                node = parent;
            }
        });
    </script>
@endsection

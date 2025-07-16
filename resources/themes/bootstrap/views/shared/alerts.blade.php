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
@if (Session::has('success') || Session::has('error') || Session::has('warning') || Session::has('info'))
    <div class="mb-2">
        @if($success = Session::get('success'))
            <div class="alert alert-success d-flex align-items-center mt-2" role="alert">
                {!! $success !!}
            </div>
        @endif
        @if($error = Session::get('error'))
            <div class="alert alert-danger d-flex align-items-center mt-2" role="alert">
                {!! $error !!}
            </div>
        @endif
        @if($warning = Session::get('warning'))
            <div class="alert alert-warning d-flex align-items-center mt-2" role="alert">
                {!! $warning !!}
            </div>
        @endif
        @if($info = Session::get('info'))
            <div class="alert alert-info d-flex align-items-center mt-2" role="alert">
                {!! $info !!}
            </div>
        @endif
    </div>
@endif

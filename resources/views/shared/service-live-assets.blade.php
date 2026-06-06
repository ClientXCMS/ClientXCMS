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
 * Year: 2026
 */
?>
{{-- v2.16 — Live service status assets.
     Loads the JS poller + CSS pill once per page. Pages that show the
     live widget should @include this partial inside their layout.
     The Vite plugin emits both the bundled JS and the companion CSS
     for the same module — see resources/global/js/service-live.js and
     resources/global/css/service-live.css. --}}
@vite(['resources/global/js/service-live.js', 'resources/global/css/service-live.css'])

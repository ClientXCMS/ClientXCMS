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

<span class="border-start ms-3"></span>
<a href="{{ route('front.store.basket.show') }}" class="nav-link position-relative ms-3">
    <i class="bi bi-cart"></i>
    @if (basket(false) != null && basket()->quantity() > 0)
        <span
            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ basket()->quantity() }}</span>
    @endif
</a>
@if (setting('theme_switch_mode') == 'both')
    <button id="dark-mode-btn"  data-url="{{ route('darkmode.switch') }}"
            class="nav-link ms-1">
        <i class="bi bi-sun @if (!is_darkmode()) d-none @endif" id="dark-mode-sun"></i>
        <i class="bi bi-moon @if (is_darkmode()) d-none @endif" id="dark-mode-moon"></i>
    </button>
@endif
<div class="dropdown">
    <button class="nav-link dropdown-toggle ms-1" type="button" id="dropdownMenuButton1"
            data-bs-toggle="dropdown" aria-expanded="false">
        <img src="{{ \App\Services\Core\LocaleService::getLocales(false)[\App\Services\Core\LocaleService::fetchCurrentLocale()]["flag"] }}" style="width: 24px; height: 24px;"
             alt="{{ \App\Services\Core\LocaleService::getLocales(false)[\App\Services\Core\LocaleService::fetchCurrentLocale()]['name'] }}">
    </button>
    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
        @foreach(\App\Services\Core\LocaleService::getLocales() as $locale => $language)
            <li>
                <a class="dropdown-item" href="{{ route('locale', ['locale' => $locale]) }}">
                    <img src="{{ $language['flag'] }}" alt="{{ $language['name'] }}" style="width: 24px; height: 24px;">
                    {{ $language['name'] }}
                </a>
            </li>
        @endforeach
</div>
@if (auth('web')->guest())

    <a href="{{ route('login') }}" class="nav-link">
        <i class="bi bi-person-fill"></i>
    </a>
@else
    <div class="dropdown">
        <button class="nav-link dropdown-toggle ms-1 bg-body-tertiary rounded" type="button" id="dropdownMenuButton2"
                data-bs-toggle="dropdown" aria-expanded="false">
            {{ Auth::user()->firstname[0] . Auth::user()->lastname[0] }}
        </button>
        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton2">
            @if (Session::has('autologin'))

                <li>
                    <a class="dropdown-item" href="{{ route('admin.customers.logout') }}">
                        <i class="bi bi-box-arrow-right  w-4 h-4 me-2"></i>
                        {{ __('admin.customers.autologin.logout') }}
                    </a>
                </li>
            @endif
            @foreach(\App\Http\Navigation\ClientNavigationMenu::getItems() as $item)
                <li>
                    <a class="dropdown-item" href="{{ route($item['route']) }}">
                        <i class="{{ $item['icon'] }} w-4 h-4 me-2"></i>

                        {{ $item['name'] }}
                        </a>
                </li>
            @endforeach
            <li>
                <a class="dropdown-item" href="#" id="logout-btn">
                    <i class="bi bi-lock w-4 h-4 me-2"></i>
                    {{ __('client.logout') }}
                </a>
            </li>
        </ul>
    </div>
@endif

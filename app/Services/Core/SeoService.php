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

namespace App\Services\Core;

use Illuminate\Support\Facades\Cache;

class SeoService
{
    private array $local = [];

    public function addHead(string $content, string $context = 'front'): void
    {
        $head = $this->get('seo_head_'.$context);
        $head .= $content;
        Cache::put('seo_head_'.$context, $head);
        $this->put('seo_head_'.$context, $head);
    }

    public function replaceInHead(string $search, string $replace, string $context = 'front'): void
    {
        $head = $this->get('seo_head_'.$context);
        $head = str_replace($search, $replace, $head);
        $this->put('seo_head_'.$context, $head);
    }

    public function replaceInFooter(string $search, string $replace, string $context = 'front'): void
    {
        $footer = $this->get('seo_footer');
        $footer = str_replace($search, $replace, $footer);
        $this->put('seo_footer', $footer);
    }

    public function replaceInHeader(string $search, string $replace, string $context = 'front'): void
    {
        $header = $this->get('seo_header_'.$context);
        $header = str_replace($search, $replace, $header);
        $this->put('seo_header_'.$context, $header);
    }

    public function addFooter(string $content, string $context = 'front'): void
    {
        $footer = $this->get('seo_footer_'.$context);
        $footer .= $content;
        $this->put('seo_footer_'.$context, $footer);
    }

    public function addHeader(string $content, string $context = 'front'): void
    {
        $header = $this->get('seo_header_'.$context);
        $header .= $content;
        $this->put('seo_header_'.$context, $header);
    }

    public function addFooterIfNotExists(string $content, string $context = 'front'): void
    {
        $footer = $this->get('seo_footer_'.$context);
        if (str_contains($footer, $content) === false) {
            $footer .= $content;
            $this->put('seo_footer_'.$context, $footer);
        }
    }

    public function addHeadIfNotExists(string $content, string $context = 'front'): void
    {
        $head = $this->get('seo_head_'.$context);
        if (str_contains($head, $content) === false) {
            $head .= $content;
            $this->put('seo_head_'.$context, $head);
        }
    }

    public function addHeaderIfNotExists(string $search, string $content, string $context = 'front'): void
    {
        $head = $this->get('seo_header_'.$context);
        if (str_contains($head, $search) === false) {
            $head .= $content;
            $this->put('seo_header_'.$context, $head);
        }
    }

    public function head(string $context = 'front', ?string $append = null): string
    {
        return $this->get('seo_head_'.$context, $this->generateHead($append)).$append;
    }

    public function foot(string $context = 'front', ?string $append = null): string
    {
        return $this->get('seo_footer_'.$context, setting('seo_footerscripts', '')).$append;
    }

    public function header(string $context = 'front', ?string $append = null): string
    {
        return $this->get('seo_header_'.$context, '').$append;
    }

    public function favicon(): string
    {
        return '<link rel="icon" type="image/png" href="'.setting('app_favicon').'">';
    }

    private function generateHead(?string $append = null): string
    {
        $head = '';
        if (setting('seo_description') && ! str_contains($append, '<meta name="description"') === false) {
            $head .= '<meta name="description" content="'.setting('seo_description').'">';
        }
        if (setting('seo_keywords')) {
            $head .= '<meta name="keywords" content="'.setting('seo_keywords').'">';
        }
        if (setting('seo_theme_color')) {
            $head .= '<meta name="theme-color" content="'.setting('seo_theme_color').'">';
        }
        if (setting('seo_headscripts')) {
            $head .= setting('seo_headscripts');
        }
        if (setting('seo_disablereferencement')) {
            $head .= '<meta name="robots" content="noindex, nofollow">';
        }

        return $head;
    }

    private function get(string $key, string $default = '')
    {
        if (isset($this->local[$key])) {
            return $this->local[$key];
        }

        return $default;
    }

    private function put(string $key, $value)
    {
        $this->local[$key] = $value;
    }
}

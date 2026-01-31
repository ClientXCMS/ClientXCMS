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

namespace App\View;

class ThemeViewFinder extends \Illuminate\View\FileViewFinder
{
    public function findInPaths($name, $paths)
    {
        $parent = app('theme')->getTheme()->getParentTheme();
        // $paths = app('view')->getFinder()->getPaths();
        $paths[] = resource_path('themes/'.$parent.'/views');

        if (strpos($paths[0] ?? '', '/vendor/')) {
            return parent::findInPaths($name, $paths);
        }
        if (env('APP_REVERSE_PATHS', false)) {
            $paths = array_reverse($paths);
        }

        return parent::findInPaths($name, $paths);
    }

    protected function parseNamespaceSegments($name): array
    {
        try {
            return parent::parseNamespaceSegments($name);
        } catch (\InvalidArgumentException $e) {
            $segments = explode(static::HINT_PATH_DELIMITER, $name);
            $segments[0] = $segments[0].'_'.app('theme')->getTheme()->getParentTheme();
            try {
                return parent::parseNamespaceSegments(implode(static::HINT_PATH_DELIMITER, $segments));
            } catch (\InvalidArgumentException $e) {
                $segments = explode(static::HINT_PATH_DELIMITER, $name);
                $segments[0] = $segments[0].'_default';

                return parent::parseNamespaceSegments(implode(static::HINT_PATH_DELIMITER, $segments));
            }
        }
    }
}

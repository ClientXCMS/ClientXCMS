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
        // Trigger lazy ThemeManager construction which may add paths via addLocation()
        $theme = app('theme')->getTheme();

        // After lazy construction, $this->paths may include theme locations.
        // Refresh $paths only for non-namespaced lookups where $paths was
        // originally a stale copy of $this->paths (all elements already
        // exist in the updated $this->paths). Namespaced lookups (e.g.
        // notifications::email) pass namespace-specific paths that must
        // be preserved.
        if (empty(array_diff($paths, $this->paths))) {
            $paths = $this->paths;
        }

        if ($theme !== null) {
            $parent = $theme->getParentTheme();
            if ($parent !== null) {
                $parentPath = resource_path('themes/'.$parent.'/views');
                if (! in_array($parentPath, $paths)) {
                    $paths[] = $parentPath;
                }
            }
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
            $theme = app('theme')->getTheme();
            $parent = $theme !== null ? $theme->getParentTheme() : null;

            if ($parent !== null) {
                $segments = explode(static::HINT_PATH_DELIMITER, $name);
                $segments[0] = $segments[0].'_'.$parent;
                try {
                    return parent::parseNamespaceSegments(implode(static::HINT_PATH_DELIMITER, $segments));
                } catch (\InvalidArgumentException $e) {
                    // Fall through to default
                }
            }

            $segments = explode(static::HINT_PATH_DELIMITER, $name);
            $segments[0] = $segments[0].'_default';

            return parent::parseNamespaceSegments(implode(static::HINT_PATH_DELIMITER, $segments));
        }
    }
}

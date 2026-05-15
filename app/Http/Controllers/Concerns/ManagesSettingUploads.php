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

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait ManagesSettingUploads
{
    protected function deleteSettingUpload(?string $path): void
    {
        $path = $this->normalizeUploadPath($path);

        if ($path && Storage::exists($path)) {
            Storage::delete($path);
        }
    }

    protected function cleanupUnusedUploads(string|array $prefixes, string|array|null $keep = null, string $directory = 'public'): void
    {
        $prefixes = (array) $prefixes;
        $keep = collect((array) $keep)
            ->map(fn ($path) => $this->normalizeUploadPath($path))
            ->filter()
            ->all();

        foreach (Storage::files($directory) as $file) {
            if (in_array($file, $keep, true)) {
                continue;
            }

            $filename = basename($file);
            foreach ($prefixes as $prefix) {
                if ($this->matchesUploadPrefix($filename, $prefix)) {
                    Storage::delete($file);
                    break;
                }
            }
        }
    }

    private function normalizeUploadPath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $path = parse_url($path, PHP_URL_PATH) ?: $path;
        $path = ltrim($path, '/');

        if (Str::startsWith($path, 'storage/')) {
            return 'public/'.Str::after($path, 'storage/');
        }

        if (Str::startsWith($path, 'public/')) {
            return $path;
        }

        return 'public/'.$path;
    }

    private function matchesUploadPrefix(string $filename, string $prefix): bool
    {
        return Str::startsWith($filename, $prefix);
    }
}

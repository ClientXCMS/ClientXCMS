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

namespace App\Console\Commands\Translations;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportTranslationCommand extends Command
{
    protected $signature = 'translations:export {--path=} {--locale=fr}';

    protected $description = 'Export a reference locale to one JSON file per module, mirroring lang/<locale>/*.php';

    public function handle(): void
    {
        $locale = $this->option('locale');
        $langDirectory = base_path("lang/{$locale}");

        if (! File::exists($langDirectory)) {
            $this->error("No {$locale} translations found.");

            return;
        }

        $outputDirectory = $this->option('path') ?: storage_path($locale);
        File::ensureDirectoryExists($outputDirectory);

        $files = File::files($langDirectory);
        $exported = 0;

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $translations = include $file->getRealPath();
            if (! is_array($translations)) {
                continue;
            }

            $module = $file->getFilenameWithoutExtension();
            $content = $this->replaceLaravelVariables($translations);
            File::put(
                "{$outputDirectory}/{$module}.json",
                json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
            $exported++;
        }

        $this->info("{$exported} {$locale} module(s) exported to {$outputDirectory}");
    }

    protected function replaceLaravelVariables(array $translations): array
    {
        foreach ($translations as $key => $value) {
            if (is_string($value)) {
                $translations[$key] = preg_replace('/:(\w+)/', '{_$1}', $value);
            } elseif (is_array($value)) {
                $translations[$key] = $this->replaceLaravelVariables($value);
            }
        }

        return $translations;
    }
}

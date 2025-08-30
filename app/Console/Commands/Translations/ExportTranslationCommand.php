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
    protected $signature = 'translations:export {--path=fr.json}';

    protected $description = 'Export French translations to a JSON file and convert PHP arrays to JSON';

    protected array $languageNames = [
        'fr' => 'Français',
    ];

    public function handle(): void
    {
        $directories = [
            base_path('lang'),
        ];

        $translationsByLocale = [];

        if (File::exists(storage_path($this->option('path')))) {
            File::delete(storage_path($this->option('path')));
        }

        foreach ($directories as $dir) {
            if (File::exists($dir)) {
                $this->processBaseDirectory($dir, $translationsByLocale);
            }
        }

        if (isset($translationsByLocale['fr'])) {
            $this->exportToJson($translationsByLocale['fr']);
        } else {
            $this->error('No French translations found.');
        }
    }

    protected function processBaseDirectory($baseDir, &$translationsByLocale): void
    {
        if ($baseDir === base_path('lang')) {
            $this->processLangDirectory($baseDir, 'lang', $translationsByLocale);
        } else {
            $baseFolder = basename($baseDir);
            $moduleDirectories = File::directories($baseDir);

            foreach ($moduleDirectories as $moduleDirectory) {
                $moduleName = basename($moduleDirectory);
                $langDirectory = $moduleDirectory.'/lang';
                if (File::exists($langDirectory)) {
                    $this->processLangDirectory($langDirectory, "{$baseFolder}.{$moduleName}.lang", $translationsByLocale);
                }
            }
        }
    }

    protected function processLangDirectory($langDirectory, $modulePrefix, &$translationsByLocale): void
    {
        $localeDirectories = File::directories($langDirectory);

        foreach ($localeDirectories as $localeDirectory) {
            $locale = basename($localeDirectory);
            if ($locale === 'fr') {
                $files = File::files($localeDirectory);

                foreach ($files as $file) {
                    if ($file->getExtension() === 'php') {
                        $this->collectTranslations($file->getRealPath(), $locale, $modulePrefix, $file->getFilename(), $translationsByLocale);
                    }
                }
            }
        }
    }

    protected function collectTranslations($filePath, $locale, $modulePrefix, $fileName, &$translationsByLocale): void
    {
        $translations = include $filePath;
        if (is_array($translations)) {
            if (! isset($translationsByLocale[$locale])) {
                $translationsByLocale[$locale] = [];
                $translationsByLocale[$locale]['language'] = $this->languageNames[$locale] ?? $locale;
            }
            $fileKey = basename($fileName, '.php');
            $modulePrefix .= '.'.$locale.'.'.$fileKey;
            $translationsByLocale[$locale][$modulePrefix] = $this->replaceLaravelVariables($translations);
        }
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

    protected function exportToJson($translations): void
    {
        $storagePath = storage_path($this->option('path'));
        $jsonContent = json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        File::put($storagePath, $jsonContent);

        $this->info('French translations have been successfully exported to '.$storagePath);
    }
}

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

use File;
use Illuminate\Console\Command;
use ZipArchive;

class ImportFilesTranslationCommand extends Command
{
    protected $signature = 'translations:import-files {--path=translations.zip}';

    protected $description = 'Import translations from a ZIP file and replace them in the project in PHP format';

    public function handle(): void
    {
        $zipFilePath = storage_path($this->option('path'));
        if (! File::exists($zipFilePath)) {
            $this->error("The ZIP file does not exist: {$zipFilePath}");

            return;
        }

        $extractedPath = storage_path('app/translations_extracted');
        if (! File::exists($extractedPath)) {
            File::makeDirectory($extractedPath, 0755, true);
        }
        $zip = new ZipArchive;
        if ($zip->open($zipFilePath) === true) {
            $zip->extractTo($extractedPath);
            $zip->close();
            $this->info('ZIP file extracted.');
        } else {
            $this->error('Error while extracting the ZIP file.');

            return;
        }

        $jsonFiles = File::files($extractedPath.'/translations');
        foreach ($jsonFiles as $jsonFile) {
            $locale = basename($jsonFile, '.json');
            if ($locale == 'fr') {
                $this->info('Skipping French locale.');

                continue;
            }
            $this->info("Processing locale: {$locale}");
            $translations = json_decode(File::get($jsonFile), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error("Error while decoding JSON file: {$jsonFile}");

                continue;
            }
            unset($translations['language']);
            $this->processTranslations($translations, $locale);
        }
        File::deleteDirectory($extractedPath);
        $this->info('Import finished.');
    }

    protected function processTranslations($translations, $locale): void
    {
        foreach ($translations as $path => $translationData) {
            $baseDir = base_path(str_replace('.', '/', $path));
            $baseDir = str_replace('/fr/', "/{$locale}/", $baseDir);
            $langDirectory = pathinfo($baseDir, PATHINFO_DIRNAME);
            if (! File::exists($langDirectory)) {
                File::makeDirectory($langDirectory, 0755, true);
                $this->info('Folder created: '.$langDirectory);
            }
            $phpFilePath = "{$baseDir}.php";

            // Appliquer l'inversion des variables {_variable} vers :variable
            $processedTranslationData = $this->restoreLaravelVariables($translationData);

            $phpContent = "<?php\n\nreturn ".$this->varExport($processedTranslationData, true).";\n";
            File::put($phpFilePath, $phpContent);
            $this->info('File created: '.$phpFilePath);
        }
    }

    protected function restoreLaravelVariables(array $translations): array
    {
        foreach ($translations as $key => $value) {
            if (is_string($value)) {
                // Inverser les variables {_variable} en :variable
                $translations[$key] = preg_replace('/{\_(\w+)}/', ':$1', $value);
            } elseif (is_array($value)) {
                // Appliquer l'inversion de manière récursive pour les sous-tableaux
                $translations[$key] = $this->restoreLaravelVariables($value);
            }
        }

        return $translations;
    }

    private function varExport($expression, $return = false)
    {
        $export = var_export($expression, true);
        $patterns = [
            "/array \(/" => '[',
            "/^([ ]*)\)(,?)$/m" => '$1]$2',
            "/=>[ ]?\n[ ]+\[/" => '=> [',
            "/([ ]*)(\'[^\']+\') => ([\[\'])/" => '$1$2 => $3',
        ];
        $export = preg_replace(array_keys($patterns), array_values($patterns), $export);
        if ((bool) $return) {
            return $export;
        } else {
            echo $export;
        }
    }
}

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
namespace App\Console\Commands\Migrate;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;

class ExportTranslations extends Command
{
    protected $signature = 'translations:export {--path=translations.zip}';

    protected $description = 'Export translations to a ZIP file and convert PHP arrays to JSON';

    protected array $languageNames = [
        'fr' => 'Français',
        'en' => 'English',
    ];

    public function handle()
    {
        $directories = [
            base_path('addons'),  // Pour les traductions dans /addons/{module}/lang/{locale}
            base_path('modules'), // Pour les traductions dans /modules/{module}/lang/{locale}
            base_path('lang'),     // Pour les traductions globales dans /lang/{locale}
        ];

        $translationsByLocale = [];

        if (File::exists(storage_path($this->option('path')))) {
            File::delete(storage_path($this->option('path')));
        }
        // Collecter les traductions
        foreach ($directories as $dir) {
            if (File::exists($dir)) {
                $this->processBaseDirectory($dir, $translationsByLocale);
            }
        }
        $this->exportToZip($translationsByLocale);
    }

    protected function processBaseDirectory($baseDir, &$translationsByLocale)
    {
        if ($baseDir === base_path('lang')) {
            // Cas spécial pour le dossier global /lang
            $this->processLangDirectory($baseDir, 'lang', $translationsByLocale);
        } else {
            // Récupère le type (addons ou modules)
            $baseFolder = basename($baseDir);

            // Parcours des modules dans /addons/{module}/lang ou /modules/{module}/lang
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

    protected function processLangDirectory($langDirectory, $modulePrefix, &$translationsByLocale)
    {
        $localeDirectories = File::directories($langDirectory);

        foreach ($localeDirectories as $localeDirectory) {
            $locale = basename($localeDirectory);
            $files = File::files($localeDirectory);

            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $this->collectTranslations($file->getRealPath(), $locale, $modulePrefix, $file->getFilename(), $translationsByLocale);
                }
            }
        }
    }

    protected function collectTranslations($filePath, $locale, $modulePrefix, $fileName, &$translationsByLocale)
    {
        $translations = include $filePath;

        if (is_array($translations)) {
            // Initialiser le tableau pour la langue si nécessaire
            if (! isset($translationsByLocale[$locale])) {
                $translationsByLocale[$locale] = [];
                // Ajouter la clé 'language' avec le nom complet de la langue
                $translationsByLocale[$locale]['language'] = $this->languageNames[$locale] ?? $locale;
            }

            // Utiliser le nom du fichier sans l'extension '.php' pour structurer les traductions
            $fileKey = basename($fileName, '.php');
            $modulePrefix .= '.'.$locale.'.'.$fileKey;
            // Initialiser le chemin pour les traductions dans la structure
            if (! isset($translationsByLocale[$locale][$modulePrefix])) {
                $translationsByLocale[$locale][$modulePrefix] = [];
            }

            // Ajouter les traductions sous cette clé dans la structure
            $translationsByLocale[$locale][$modulePrefix] = $translations;
        }
    }

    protected function exportToZip($translationsByLocale)
    {
        $storagePath = storage_path($this->option('path'));

        // Supprimer le zip s'il existe déjà
        if (File::exists($storagePath)) {
            File::delete($storagePath);
        }

        // Créer une nouvelle archive ZIP
        $zip = new ZipArchive;
        if ($zip->open($storagePath, ZipArchive::CREATE) === true) {
            foreach ($translationsByLocale as $locale => $translations) {
                // Convertir les traductions en JSON
                $jsonContent = json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                // Créer le fichier JSON pour la langue (fr.json, en.json, ...)
                $jsonFileName = "{$locale}.json";

                // Ajouter le fichier JSON au zip
                $zip->addFromString("translations/{$jsonFileName}", $jsonContent);
            }

            $zip->close();
            $this->info("Les fichiers de traduction structurés ont été exportés avec succès vers {$storagePath}");
        } else {
            $this->error('Impossible de créer le fichier ZIP');
        }
    }
}

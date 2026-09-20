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

class ImportFileTranslationCommand extends Command
{
    protected $signature = 'translations:import-file {--path=} {--locale=} {--prune : Remove lang/<locale>/*.php modules not present in the imported set}';

    protected $description = 'Import a directory of per-module JSON files into lang/<locale>/*.php';

    public function handle(): void
    {
        $directory = storage_path($this->option('path'));
        $locale = $this->option('locale') ?: basename($directory);

        if (! File::isDirectory($directory)) {
            $this->error("The translations directory does not exist: {$directory}");

            return;
        }

        $this->info("Processing locale: {$locale}");
        $imported = [];

        foreach (File::files($directory) as $file) {
            if ($file->getExtension() !== 'json') {
                continue;
            }

            $translations = json_decode(File::get($file->getRealPath()), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error("Error while decoding JSON file: {$file->getFilename()}");

                continue;
            }

            $module = $file->getFilenameWithoutExtension();
            $this->writeModule($locale, $module, $translations);
            $imported[] = $module;
        }

        $this->info(count($imported)." {$locale} module(s) imported.");

        if ($this->option('prune')) {
            $this->pruneStaleModules($locale, $imported);
        }
    }

    /**
     * Removes lang/<locale>/*.php files that fr no longer has, so a target
     * locale stays a strict mirror of fr's own module list.
     */
    protected function pruneStaleModules(string $locale, array $importedModules): void
    {
        $langDirectory = base_path("lang/{$locale}");
        if (! File::isDirectory($langDirectory)) {
            return;
        }

        foreach (File::files($langDirectory) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $module = $file->getFilenameWithoutExtension();
            if (! in_array($module, $importedModules, true)) {
                File::delete($file->getRealPath());
                $this->info("Removed stale module: {$module}.php");
            }
        }
    }

    protected function writeModule(string $locale, string $module, array $translations): void
    {
        $langDirectory = base_path("lang/{$locale}");
        if (! File::exists($langDirectory)) {
            File::makeDirectory($langDirectory, 0755, true);
        }

        $processed = $this->restoreLaravelVariables($translations);
        $phpContent = "<?php\n\nreturn ".$this->varExport($processed, true).";\n";
        File::put("{$langDirectory}/{$module}.php", $phpContent);
    }

    protected function restoreLaravelVariables(array $translations): array
    {
        foreach ($translations as $key => $value) {
            if (is_string($value)) {
                $translations[$key] = preg_replace('/{\_(\w+)}/', ':$1', $value);
            } elseif (is_array($value)) {
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

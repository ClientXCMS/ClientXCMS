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

namespace App\Console\Commands\Extension;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CreateThemeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     *
    */
    protected $signature = 'clientxcms:create-theme {--name=: Theme name}
    {--uuid= : Theme UUID}
    {--parent=default : Parent theme: default or bootstrap}
    {--description= : Description}
    {--author-name= : Author name}
    {--author-email= : Author email}
    {--css=1 : Create CSS file}
    {--js=1 : Create JS file}
    {--config=1 : Create config files}
    {--lang=1 : Create lang files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new theme for the CLIENTXCMS.';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $name = $this->option('name');
        $uuid = sanitize($this->option('uuid'));
        $parent = $this->option('parent') ?: 'default';

        if (! $name || ! $uuid) {
            $this->error('Options required: --name and --uuid');
            return self::FAILURE;
        }

        $description = $this->option('description') ?: $this->ask('What is the description of the theme?', "A theme for CLIENTXCMS named $name.");
        $author_name = $this->option('author-name') ?: $this->ask('What is the name of the author?');
        $author_email = $this->option('author-email') ?: $this->ask('What is the email of the author?');
        if (File::exists(resource_path("themes/$uuid"))) {
            $this->error('The theme already exists.');

            return;
        }
        $this->info("Creating a new theme named $name...");
        File::makeDirectory(resource_path("themes/$uuid/views"), 0755, true, true);

        $this->info("Creating a new theme named $name...");
        File::put(resource_path("themes/$uuid/theme.json"), json_encode([
            'name' => $name,
            'uuid' => $uuid,
            'description' => $description,
            'version' => '1.0',
            'author' => [
                'name' => $author_name,
                'email' => $author_email,
            ],
            'unofficial' => true,
            'parent_theme' => $parent,
        ], JSON_PRETTY_PRINT));
        $css = $this->hasOption('css') ? filter_var($this->option('css'), FILTER_VALIDATE_BOOLEAN) : $this->confirm('Do you make a CSS file for this theme?', true);
        if ($css) {
            File::makeDirectory(resource_path("themes/$uuid/css"), 0755, true, true);
            File::put(resource_path("themes/$uuid/css/app.css"), "@tailwind base;
@tailwind components;
@tailwind utilities;
@import 'bootstrap-icons/font/bootstrap-icons.min.css';
@import 'flatpickr/dist/flatpickr.min.css';
/* Your CSS code here */");
        }
        $js = $this->hasOption('js') ? filter_var($this->option('js'), FILTER_VALIDATE_BOOLEAN) : $this->confirm('Do you make a JS file for this theme?', true);
        File::makeDirectory(resource_path("themes/$uuid/js"), 0755, true, true);
        if ($js) {
            File::put(resource_path("themes/$uuid/js/app.js"), "import 'preline'
import.meta.glob([
    '/resources/global/**',
    '/resources/global/js/**',
]);
");
        }
        $config = $this->hasOption('config') ? filter_var($this->option('config'), FILTER_VALIDATE_BOOLEAN) : $this->confirm('Do you make a config file for this theme?', true);
        if ($config) {
            File::makeDirectory(resource_path("themes/$uuid/config"), 0755, true, true);
            File::put(resource_path("themes/$uuid/config/config.php"), "<?php\n\nreturn [];");
            File::put(resource_path("themes/$uuid/config/config.json"), json_encode([], JSON_PRETTY_PRINT));
            File::put(resource_path("themes/$uuid/config/config.blade.php"), '');
            $this->info('Config created successfully.');
        }
        $lang = $this->hasOption('lang') ? filter_var($this->option('lang'), FILTER_VALIDATE_BOOLEAN) : $this->confirm('Do you make a lang file for this theme?', true);
        if ($lang) {
            File::makeDirectory(resource_path("themes/$uuid/lang"), 0755, true, true);
            File::makeDirectory(resource_path("themes/$uuid/lang/en"), 0755, true, true);
            File::makeDirectory(resource_path("themes/$uuid/lang/fr"), 0755, true, true);
            File::put(resource_path("themes/$uuid/lang/en/messages.php"), json_encode([], JSON_PRETTY_PRINT));
            File::put(resource_path("themes/$uuid/lang/fr/messages.php"), json_encode([], JSON_PRETTY_PRINT));
            $this->info('Lang created successfully.');
        }
        $this->info('Theme created successfully.');
    }

    public function copyDirectory(string $source, string $destination, string $uuid): bool
    {
        if (! File::isDirectory($source)) {
            return false;
        }
        if (! File::isDirectory($destination)) {
            File::makeDirectory($destination, 0755, true);
        }
        $files = File::files($source);
        $directories = File::directories($source);
        foreach ($files as $file) {
            $destFilePath = $destination.'/'.File::basename($file);
            File::copy($file, $destFilePath);
            $content = File::get($destFilePath);
            $content = str_replace('$THEME_NAME', $uuid, $content);
            File::put($destFilePath, $content);
        }

        foreach ($directories as $directory) {
            $destDirPath = $destination.'/'.File::basename($directory);
            $this->copyDirectory($directory, $destDirPath, $uuid);
        }

        return true;
    }
}

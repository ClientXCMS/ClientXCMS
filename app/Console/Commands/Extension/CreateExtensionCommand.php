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

class CreateExtensionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clientxcms:create-extension {--name= : Extension name} {--uuid= : Extension UUID}
    {--description=This is a new extension.}
    {--type=addon : addon or module}
    {--author-name= : Author name}
    {--author-email= : Author email}
    {--migrations=0}
    {--models=0}
    {--controllers=1}
    {--lang=0}
    {--routes=0}
    {--web=0}
    {--api=0}
    {--admin=0}
    {--views=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new extension for the CLIENTXCMS.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->option('name') ?: sanitize($this->ask('What is the name of the extension?'));
        $uuid = $this->option('uuid') ?: strtolower(sanitize($this->ask('What is the UUID of the extension?', str_replace(' ', '-', strtolower($name)))));
        $description = $this->option('description') ?: sanitize($this->ask('What is the description of the extension?', 'This is a new extension.'));
        $type = $this->option('type') ?: $this->choice('What type of extension is this?', ['addon', 'module']);
        if (! in_array($type, ['addon', 'module'])) {
            $this->error('Invalid extension type.');

            return;
        }
        $type = $type . 's';
        if (File::exists(base_path($type . "/$uuid"))) {
            $this->error('The extension already exists.');
            return;
        }
        File::makeDirectory(base_path($type . "/$uuid"), 0755, true, true);
        File::makeDirectory(base_path($type . "/$uuid/src"), 0755, true, true);
        $this->info("Creating a new $type extension named $name...");
        $migrations = $this->hasOption('migrations') ? filter_var($this->option('migrations'), FILTER_VALIDATE_BOOLEAN) : $this->confirm('Do you make migrations for this extension?');
        if ($migrations) {
            File::makeDirectory(base_path($type . "/$uuid/database/migrations"), 0755, true, true);
        }
        $models = $this->hasOption('models') ? filter_var($this->option('models'), FILTER_VALIDATE_BOOLEAN) : $this->confirm('Do you make models for this extension?');
        if ($models) {
            File::makeDirectory(base_path($type . "/$uuid/src/Models"), 0755, true, true);
        }
        $controllers = $this->hasOption('controllers') ? filter_var($this->option('controllers'), FILTER_VALIDATE_BOOLEAN) : $this->confirm('Do you make controllers for this extension?', true);
        if ($controllers) {
            File::makeDirectory(base_path($type . "/$uuid/src/Controllers"), 0755, true, true);
        }
        $author_name = $this->option('author-name') ?: $this->ask('What is the name of the author?');
        $author_email = $this->option('author-email') ?: $this->ask('What is the email of the author?');
        $lang = $this->hasOption('lang') ? filter_var($this->option('lang'), FILTER_VALIDATE_BOOLEAN) : $this->confirm('Do you make lang for this extension?');
        if ($lang) {
            File::makeDirectory(base_path($type . "/$uuid/lang/fr"), 0755, true, true);
            File::makeDirectory(base_path($type . "/$uuid/lang/en"), 0755, true, true);
            File::put(base_path($type . "/$uuid/lang/fr/lang.php"), "<?php\n\n return [];");
            File::put(base_path($type . "/$uuid/lang/en/lang.php"), "<?php\n\n return [];");
        }
        if ($this->hasOption('routes') ? filter_var($this->option('routes'), FILTER_VALIDATE_BOOLEAN) : $this->confirm('Do you make routes for this extension?')) {
            File::makeDirectory(base_path($type . "/$uuid/routes"), 0755, true, true);
            if ($this->hasOption('web') ? filter_var($this->option('web'), FILTER_VALIDATE_BOOLEAN) : $this->confirm('Do you make a web.php file for this extension?')) {
                File::put(base_path($type . "/$uuid/routes/web.php"), "<?php\n\n");
            }
            if ($this->hasOption('api') ? filter_var($this->option('api'), FILTER_VALIDATE_BOOLEAN) : $this->confirm('Do you make an api.php file for this extension?')) {
                File::put(base_path($type . "/$uuid/routes/api.php"), "<?php\n\n");
            }
            if ($this->hasOption('admin') ? filter_var($this->option('admin'), FILTER_VALIDATE_BOOLEAN) : $this->confirm('Do you make a admin.php file for this extension?')) {
                File::put(base_path($type . "/$uuid/routes/admin.php"), "<?php\n\n");
            }
        }
        $views = $this->hasOption('views') ? filter_var($this->option('views'), FILTER_VALIDATE_BOOLEAN) : $this->confirm('Do you make views for this extension?', true);
        if ($views) {
            File::makeDirectory(base_path($type . "/$uuid/views/default"), 0755, true, true);
            File::makeDirectory(base_path($type . "/$uuid/views/admin"), 0755, true, true);
        }
        $nameServiceProvider = ucfirst($name) . 'ServiceProvider';
        $_type = substr($type, 0, -1);
        $typeUppercase = ucfirst($type);
        $_typeUppercase = ucfirst($_type);
        File::put(base_path($type . "/$uuid/composer.json"), $this->composerJson($name, $uuid, $type, $description));
        File::put(base_path($type . "/$uuid/{$_type}.json"), json_encode([
            'name' => $name,
            'description' => $description,
            'uuid' => $uuid,
            'version' => '1.0',
            'author' => [
                'name' => $author_name,
                'email' => $author_email,
            ],
            'providers' => [
                "App\\$typeUppercase\\$name\\$nameServiceProvider",
            ],
        ], JSON_PRETTY_PRINT));
        $property = 'protected string $uuid = "' . $uuid . '";' . "\n\n";
        File::put(base_path($type . "/$uuid/src/$nameServiceProvider.php"), "<?php\n\nnamespace App\\$typeUppercase\\$name;\n\nuse \App\Extensions\Base" . $_typeUppercase . "ServiceProvider;\n\nclass $nameServiceProvider extends Base" . $_typeUppercase . "ServiceProvider\n{\n  " . $property . "  public function register()\n    {\n        //\n    }\n\n    public function boot()\n    {\n        //\n    }\n}");
        $this->info("Extension $name created successfully.");
    }

    private function composerJson(string $name, string $uuid, string $type, string $description)
    {
        $_type = 'clientxcms-' . substr($type, 0, -1);
        $composer = [
            'name' => "clientxcms/$uuid",
            'description' => $description,
            'type' => $_type,
            'require' => [
                'php' => '>=8.0',
            ],
            'config' => [
                'optimize-autoloader' => true,
                'platform-check' => false,
            ],
        ];
        $__type = ucfirst($type);
        $name = ucfirst($name);
        $composer['autoload']['psr-4']["App\\$__type\\$name\\"] = 'src/';

        return json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}

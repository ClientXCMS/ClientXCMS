<?php

namespace Database\Seeders;

use App\Models\Admin\EmailTemplate;
use App\Services\Mail\StoredTemplateMigrator;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = $this->templates();
        $extensions = app('extension')->getAllExtensions(false, true);
        foreach ($extensions as $extension) {
            if (! in_array($extension->type(), ['modules', 'addons'])) {
                continue;
            }
            $path = $extension->type().'/'.$extension->uuid.'/emails.json';
            if (file_exists(base_path($path))) {
                $extensionTemplates = json_decode(file_get_contents(base_path($path)), true);
                $templates = array_merge($templates, $extensionTemplates);
            }
        }
        $migrator = app(StoredTemplateMigrator::class);
        foreach ($templates as $name => $localeTemplates) {
            foreach ($localeTemplates as $locale => $template) {
                if (EmailTemplate::where('name', $name)->where('locale', $locale)->exists()) {
                    continue;
                }
                // Extensions ship Blade-era templates we cannot fix at the source.
                EmailTemplate::firstOrCreate([
                    'name' => $name,
                    'subject' => $migrator->rewrite($template['subject']),
                    'content' => $migrator->rewrite($template['body']),
                    'button_text' => $template['button'] ?? null,
                    'locale' => $locale,
                ]);
                $this->command->info("Created email template: {$name} ({$locale})");
            }
        }
    }

    private function templates(): array
    {
        return json_decode(file_get_contents(resource_path('emails.json')), true);
    }
}

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
namespace Database\Seeders;

use App\Models\Admin\EmailTemplate;
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
            if (! in_array($extension->type, ['modules', 'addons'])) {
                continue;
            }
            $path = $extension->type.'/'.$extension->uuid.'/emails.json';
            if (file_exists(base_path($path))) {
                $extensionTemplates = json_decode(file_get_contents(base_path($path)), true);
                $templates = array_merge($templates, $extensionTemplates);
            }
        }
        foreach ($templates as $name => $localeTemplates) {
            foreach ($localeTemplates as $locale => $template) {
                if (EmailTemplate::where('name', $name)->where('locale', $locale)->exists()) {
                    continue;
                }
                EmailTemplate::firstOrCreate([
                    'name' => $name,
                    'subject' => $template['subject'],
                    'content' => $template['body'],
                    'button_text' => $template['button'] ?? null,
                    'locale' => $locale,
                ]);
            }
        }
    }

    private function templates(): array
    {
        return json_decode(file_get_contents(resource_path('emails.json')), true);
    }
}

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


namespace App\Http\Controllers\Admin\Personalization;

use App\Http\Controllers\Admin\AbstractCrudController;
use App\Models\Admin\EmailTemplate;
use App\Models\Admin\Setting;
use App\Models\Personalization\Section;
use App\Services\Core\LocaleService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class EmailTemplateController extends AbstractCrudController
{
    protected string $model = EmailTemplate::class;

    protected string $viewPath = 'admin.personalization.email_templates';

    protected string $translatePrefix = 'personalization.email_templates';

    protected string $routePath = 'admin.personalization.email_templates';

    protected array $filters = ['name'];

    protected string $filterField = 'name';

    protected string $searchField = 'name';

    protected ?string $managedPermission = 'admin.manage_personalization';

    protected function queryIndex(): LengthAwarePaginator
    {
        return parent::queryIndex();
    }

    protected function getIndexParams($items, $translatePrefix): array
    {
        $params = parent::getIndexParams($items, $translatePrefix);
        $translations = __('personalization.email_templates.names');
        $params['translations'] = $translations;
        $params['locales'] = LocaleService::getLocalesNames(false);
        $params['configForm'] = '';

        return $params;
    }

    protected function getIndexFilters()
    {
        $locales_db = EmailTemplate::pluck('name')->unique()->toArray();
        $fields = [];
        $translations = __('personalization.email_templates.names');
        foreach ($locales_db as $db) {
            $fields[$db] = $translations[$db] ?? $db;
        }

        return $fields;
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $validated = $request->validate([
            'name' => 'required',
            'subject' => 'required',
            'content' => 'required',
            'button_text' => 'required',
        ]);
        $bannedValues = Section::TAGS_DISABLED;
        foreach ($bannedValues as $bannedValue) {
            if (str_contains($validated['content'], $bannedValue)) {
                return back()->with('error', sprintf('Tag %s is not allowed', $bannedValue));
            }
        }
        $validated['hidden'] = $request->has('hidden');
        $this->checkPermission('update');
        $emailTemplate->update($validated);
        $emailTemplate->save();

        return $this->updateRedirect($emailTemplate);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'subject' => 'required',
            'content' => 'required',
            'button_text' => 'required',
        ]);

        $bannedValues = Section::TAGS_DISABLED;
        foreach ($bannedValues as $bannedValue) {
            if (str_contains($validated['content'], $bannedValue)) {
                return back()->with('error', 'Tag '.$bannedValue.' is not allowed');
            }
        }
        $this->checkPermission('create');
        $validated['hidden'] = $request->has('hidden');
        $emailTemplate = null;
        foreach (LocaleService::getLocalesNames() as $locale => $name) {
            $validated['locale'] = $locale;
            $emailTemplate = EmailTemplate::create($validated);
        }

        return $this->storeRedirect($emailTemplate);
    }

    public function getCreateParams()
    {
        $createParams = parent::getCreateParams();
        $createParams['locales'] = LocaleService::getLocalesNames();

        return $createParams;
    }

    public function showView(array $params)
    {
        $params['translations'] = __('personalization.email_templates.names');
        $params['locales'] = LocaleService::getLocalesNames();

        return parent::showView($params);
    }

    public function show(EmailTemplate $emailTemplate)
    {
        return $this->showView(['item' => $emailTemplate]);
    }

    public function destroy(EmailTemplate $emailTemplate)
    {
        $this->checkPermission('delete');
        $emailTemplate->delete();

        return $this->deleteRedirect($emailTemplate);
    }

    public function import(Request $request)
    {
        $this->checkPermission('import');
        $validated = $request->validate([
            'file' => 'nullable|file',
            'email_template_image' => 'nullable|url|string',
            'email_template_title' => 'nullable|string',
            'email_template_description' => 'nullable|string',
        ]);
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            if (! $file->getMimeType() == 'text/html' || ! str_ends_with($file->getClientOriginalName(), '.blade.php')) {
                return redirect()->back()->with('error', __('personalization.email_templates.import.invalid_file'));
            }
            $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $name = \Str::slug($name, '.');
            $content = file_get_contents($file->getRealPath());
            $bannedValues = Section::TAGS_DISABLED;
            foreach ($bannedValues as $bannedValue) {
                if (str_contains($content, $bannedValue)) {
                    return redirect()->back()->with('error', sprintf('Tag %s is not allowed', $bannedValue));
                }
            }
            EmailTemplate::saveTemplate($name, $file);
        }
        if ($request->remove_file == 'true') {
            $validated['email_template_name'] = null;
            EmailTemplate::removeTemplate();
        } else {
            $validated['email_template_name'] = $name ?? \setting('email_template_name');
        }
        Setting::updateSettings($validated);

        return redirect()->route($this->routePath.'.index')->with('success', __('personalization.email_templates.import.success'));
    }
}

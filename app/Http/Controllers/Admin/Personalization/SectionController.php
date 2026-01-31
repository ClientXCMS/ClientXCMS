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

use App\DTO\Core\Extensions\ThemeSectionDTO;
use App\Http\Controllers\Admin\AbstractCrudController;
use App\Models\Admin\Permission;
use App\Models\Personalization\Section;
use App\Services\Core\LocaleService;
use App\Theme\ThemeManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SectionController extends AbstractCrudController
{
    protected ?string $managedPermission = Permission::MANAGE_PERSONALIZATION;

    protected string $model = Section::class;

    protected string $routePath = 'admin.personalization.sections';

    protected string $viewPath = 'admin.personalization.sections';

    protected string $translatePrefix = 'personalization.sections';

    protected function getIndexParams($items, string $translatePrefix)
    {
        $params = parent::getIndexParams($items, $translatePrefix);
        $params['pages'] = app('theme')->getSectionsPages();
        $params['sectionTypes'] = app('theme')->getSectionsTypes();
        $params['themeSections'] = app('theme')->getThemeSections();
        $params['uuid'] = request()->get('active_page');
        $params['active_page'] = collect($params['pages'])->filter(function ($v, $k) use ($params) {
            return $k == $params['uuid'];
        })->first();

        return $params;
    }

    public function show(Section $section)
    {
        $pages = app('theme')->getSectionsPages(false);
        $sectionTypes = app('theme')->getSectionsTypes();
        if (! view()->exists($section->path)) {
            if (\Str::start($section->path, 'advanced_personalization')) {
                return back()->with('error', __('personalization.sections.errors.advanced_personalization'));
            }

            return back()->with('error', __('personalization.sections.errors.notfound'));
        }
        $content = ThemeSectionDTO::fromModel($section)->getContent();
        $pages = collect($pages)->mapWithKeys(function ($item) {
            return [$item['url'] => $item['title']];
        })->toArray();
        $themes = app('theme')->getThemes();
        $themes = collect($themes)->mapWithKeys(function ($item) {
            return [$item->uuid => $item->name];
        })->toArray();

        return $this->showView(['item' => $section, 'content' => $content, 'pages' => $pages, 'sectionTypes' => $sectionTypes, 'themes' => $themes]);
    }

    public function destroy(Section $section)
    {
        $section->delete();
        ThemeManager::clearCache();

        return $this->deleteRedirect($section);
    }

    public function update(Request $request, Section $section)
    {
        $validated = $request->validate([
            'content' => ['nullable', 'string', new \App\Rules\ValidHtmlWithoutBlade],
            'url' => 'required',
            'theme_uuid' => 'required',
        ]);
        $validated['is_active'] = $request->has('is_active');
        if (! is_sanitized($request->get('content'))) {
            return back()->with('error', __('personalization.sections.errors.sanitized_content'))->withInput();
        }
        unset($validated['content']);
        $section->saveContent($request->get('content'));
        $section->update($validated);
        ThemeManager::clearCache();

        return $this->updateRedirect($section);
    }

    public function switch(Section $section)
    {
        $section->is_active = ! $section->is_active;
        $section->save();
        ThemeManager::clearCache();

        return back();
    }

    public function restore(Section $section)
    {
        $section->restore();
        ThemeManager::clearCache();

        return $this->updateRedirect($section);
    }

    public function sort(Request $request)
    {
        $items = $request->get('items');
        $i = 0;
        foreach ($items as $id) {
            Section::where('id', $id)->update(['order' => $i]);
            $i++;
        }
        ThemeManager::clearCache();

        return response()->json(['success' => true]);
    }

    public function clone(Section $section)
    {
        $newSection = $section->cloneSection();
        ThemeManager::clearCache();

        return $this->storeRedirect($newSection);
    }

    public function cloneSection(string $uuid)
    {
        $sections = app('theme')->getThemeSections();
        $section = collect($sections)->firstWhere('uuid', $uuid);
        if (! view()->exists($section->json['path'])) {
            if (\Str::start($section->json['path'], 'advanced_personalization')) {
                return back()->with('error', __('personalization.sections.errors.advanced_personalization'));
            }

            return back()->with('error', __('personalization.sections.errors.notfound'));
        }

        $pages = app('theme')->getSectionsPages();
        $uuid = request()->get('active_page');
        $active = collect($pages)->filter(function ($v, $k) use ($uuid) {
            return $k == $uuid;
        })->first();
        $theme = app('theme')->getTheme();
        Section::insert([
            'uuid' => $section->uuid,
            'path' => $section->json['path'],
            'order' => 0,
            'theme_uuid' => $theme->uuid,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'url' => $active['url'] ?? '/',
        ]);
        ThemeManager::clearCache();

        return back();
    }

    /**
     * Show the section configuration page
     */
    public function showConfig(Section $section)
    {
        if (!$section->isConfigurable()) {
            return back()->with('error', __('personalization.sections.errors.not_configurable'));
        }

        $fields = $section->getConfigurableFields();
        $values = [];
        $locales = LocaleService::getLocales();

        try {
            foreach ($fields as $field) {
                if ($field['translatable'] ?? false) {
                    foreach (array_keys($locales) as $locale) {
                        $values[$field['key']][$locale] = $section->getSetting(
                            $field['key'],
                            $field['default'] ?? null,
                            $locale
                        );
                    }
                } else {
                    $values[$field['key']] = $section->getSetting(
                        $field['key'],
                        $field['default'] ?? null
                    );
                }
            }
        } catch (\Exception $e) {
            foreach ($fields as $field) {
                $values[$field['key']] = $field['default'] ?? null;
            }
        }

        return view('admin.personalization.sections.config', [
            'section' => $section,
            'fields' => $fields,
            'values' => $values,
            'locales' => $locales,
        ]);
    }

    /**
     * Update the section configuration
     */
    public function updateConfig(Request $request, Section $section)
    {
        if (!$section->isConfigurable()) {
            return back()->with('error', __('personalization.sections.errors.not_configurable'));
        }

        $fields = $section->getConfigurableFields();
        $locales = array_keys(LocaleService::getLocales());

        $validationRules = $this->buildValidationRules($fields, $locales);
        if (!empty($validationRules)) {
            $request->validate($validationRules);
        }

        foreach ($fields as $field) {
            $key = $field['key'];

            if ($field['type'] === 'image') {
                if ($request->hasFile($key)) {
                    $oldValue = $section->getSetting($key);
                    if ($oldValue && Storage::exists($oldValue)) {
                        Storage::delete($oldValue);
                    }
                    $path = $request->file($key)->store('public/sections/' . $section->id);
                    $section->setSetting($key, $path);
                } elseif ($request->has('remove_' . $key)) {
                    $oldValue = $section->getSetting($key);
                    if ($oldValue && Storage::exists($oldValue)) {
                        Storage::delete($oldValue);
                    }
                    $section->deleteSetting($key);
                }
                continue;
            }

            if ($field['type'] === 'repeater') {
                $value = $request->input($key, []);
                if (!is_array($value)) {
                    $value = json_decode($value, true) ?? [];
                }
                $section->setSetting($key, $value);
                continue;
            }

            if ($field['translatable'] ?? false) {
                foreach ($locales as $locale) {
                    $value = $request->input("{$key}.{$locale}");
                    if ($value !== null && $value !== '') {
                        $section->setSetting($key, $value, $locale);
                    } elseif ($value === '' || $value === null) {
                        $section->deleteSetting($key, $locale);
                    }
                }
            } else {
                $value = $request->input($key);
                if ($field['type'] === 'boolean') {
                    // Handle boolean from AJAX ('1'/'0') or form submission (presence)
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $request->has($key);
                }
                if ($value !== null) {
                    $section->setSetting($key, $value);
                }
            }
        }

        ThemeManager::clearCache();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('personalization.sections.config.success'),
            ]);
        }

        return back()->with('success', __('personalization.sections.config.success'));
    }

    /**
     * Build Laravel validation rules from field definitions
     */
    private function buildValidationRules(array $fields, array $locales): array
    {
        $rules = [];

        foreach ($fields as $field) {
            $key = $field['key'];
            $fieldRules = [];

            if ($field['required'] ?? false) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            if (isset($field['validation'])) {
                $fieldRules = array_merge($fieldRules, (array) $field['validation']);
            } else {
                switch ($field['type']) {
                    case 'number':
                        $fieldRules[] = 'numeric';
                        if (isset($field['min'])) {
                            $fieldRules[] = 'min:' . $field['min'];
                        }
                        if (isset($field['max'])) {
                            $fieldRules[] = 'max:' . $field['max'];
                        }
                        break;
                    case 'image':
                        $fieldRules[] = 'image';
                        $fieldRules[] = 'max:2048';
                        break;
                }
            }

            if ($field['translatable'] ?? false) {
                foreach ($locales as $locale) {
                    $rules["{$key}.{$locale}"] = $fieldRules;
                }
            } else {
                $rules[$key] = $fieldRules;
            }
        }

        return $rules;
    }
}

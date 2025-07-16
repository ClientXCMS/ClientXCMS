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
namespace App\Http\Controllers\Admin\Personalization;

use App\DTO\Core\Extensions\ThemeSectionDTO;
use App\Http\Controllers\Admin\AbstractCrudController;
use App\Models\Admin\Permission;
use App\Models\Personalization\Section;
use App\Theme\ThemeManager;
use Illuminate\Http\Request;

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
            'content' => 'required',
            'url' => 'required',
            'theme_uuid' => 'required',
        ]);
        $validated['is_active'] = $request->has('is_active');
        foreach (Section::TAGS_DISABLED as $tag) {
            if (str_contains($request->get('content'), $tag)) {
                return back()->with('error', 'Tag '.$tag.' is not allowed');
            }
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
}

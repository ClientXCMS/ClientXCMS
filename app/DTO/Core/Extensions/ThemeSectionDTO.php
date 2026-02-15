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

namespace App\DTO\Core\Extensions;

use App\Models\Personalization\Section;
use File;

class ThemeSectionDTO
{
    public array $json;

    public string $uuid;

    public function __construct(array $json)
    {
        $this->json = $json;
        $this->uuid = $json['uuid'];
    }

    public static function fromModel(Section $section)
    {
        $api = (new Section(['uuid' => $section->uuid]))->api();
        $api['path'] = $section->path;
        $api['uuid'] = $section->uuid;

        return new self($api);
    }

    public static function fromPathInfo(array $pathinfo, array|string $path, string $uuid)
    {
        $api = (new Section(['uuid' => $uuid]))->api();
        $api['path'] = $path;
        $api['uuid'] = $uuid;

        return new self($api);
    }

    public function isActivable(): bool
    {
        $extension_needed = $this->json['extension_needed'] ?? false;
        if ($extension_needed) {
            return app('extension')->extensionIsEnabled($extension_needed);
        }

        return true;
    }

    public function thumbnail(): string
    {
        return $this->json['thumbnail'] ?? 'https://via.placeholder.com/1000x250';
    }

    public function render(bool $useCache = true): string
    {
        $path = $this->json['path'];
        try {
            if ($useCache && app()->isProduction()) {
                $cachedHtml = app('theme')->getSetting()['sections_html'] ?? collect();
                if ($cachedHtml->has($path)) {
                    return $cachedHtml->get($path);
                }
            }
            if (! view()->exists($path)) {
                return '';
            }

            return $this->renderIsolated($path);
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function renderIsolated(string $path): string
    {
        $factory = app('view');

        $sectionsRef = new \ReflectionProperty($factory, 'sections');
        $stackRef = new \ReflectionProperty($factory, 'sectionStack');
        $renderCountRef = new \ReflectionProperty($factory, 'renderCount');
        $savedSections = $sectionsRef->getValue($factory);
        $savedStack = $stackRef->getValue($factory);
        $savedRenderCount = $renderCountRef->getValue($factory);

        try {
            return view($path, $this->getContextFromUuid())->render();
        } catch (\Throwable $e) {
            $sectionsRef->setValue($factory, $savedSections);
            $stackRef->setValue($factory, $savedStack);
            $renderCountRef->setValue($factory, $savedRenderCount);

            return '';
        }
    }

    public function getContent(): string
    {
        $path = $this->json['path'];
        $content = File::get(app('view')->getFinder()->find($path));
        if (! $this->isProtected()) {
            return sanitize_content($content);
        }

        return $content;
    }

    public function isDefault(): bool
    {
        return $this->json['default'] ?? false;
    }

    private function getContextFromUuid()
    {
        $extension = app('extension');

        return $extension->getSectionsContexts()->get($this->uuid, []);
    }

    public function isProtected(): bool
    {
        return $this->json['protected'] ?? false;
    }
}

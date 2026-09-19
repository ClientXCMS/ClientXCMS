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

namespace App\Services\Content;

use App\Models\Admin\EmailTemplate;
use App\Models\Personalization\Section;
use App\Services\Mail\KnownMailVariables;
use App\Services\Mail\LegacySyntaxReport;
use App\Services\Mail\LegacySyntaxScanner;
use App\Services\Personalization\SectionScriptScanner;
use Illuminate\Support\Facades\File;

/**
 * Collects what an install stores as staff-written content, and what about it
 * will not survive the closed template grammar. Reads only; the presentation
 * lives in the command.
 */
class EditableContentAudit
{
    /** Settings that are rendered like a template even though they are not one. */
    private const RENDERED_SETTINGS = ['mail_greeting', 'mail_salutation'];

    public function __construct(
        private readonly LegacySyntaxScanner $syntax,
        private readonly SectionScriptScanner $scripts,
        private readonly KnownMailVariables $variables,
    ) {}

    /**
     * @return list<array{name: string, locale: string, field: string, report: LegacySyntaxReport}>
     */
    public function templates(): array
    {
        $rows = [];
        foreach ($this->templateFields() as [$template, $field, $value]) {
            $rows[] = [
                'name' => $template->name,
                'locale' => $template->locale,
                'field' => $field,
                'report' => $this->syntax->scan($value),
            ];
        }

        return $rows;
    }

    /**
     * A variable nothing fills ships as literal text in the message. Worth
     * catching before an upgrade, and the only way to catch a plain typo.
     *
     * @return list<list<string>>
     */
    public function unknownVariables(): array
    {
        $rows = [];
        foreach ($this->templateFields() as [$template, $field, $value]) {
            foreach ($this->variables->unknownIn($value) as $unknown) {
                $rows[] = [$template->name, $template->locale, $field, $unknown];
            }
        }

        return $rows;
    }

    /**
     * The opening and closing lines are settings, not templates, yet they go
     * through the same renderer. Auditing one without the other proves nothing.
     *
     * @return list<list<string>>
     */
    public function settings(): array
    {
        $rows = [];
        foreach (self::RENDERED_SETTINGS as $key) {
            $value = (string) setting($key);
            foreach ([...$this->syntax->scan($value)->manual, ...$this->variables->unknownIn($value)] as $finding) {
                $rows[] = [$key, $finding];
            }
        }

        return $rows;
    }

    /**
     * Edited sections only. A section still served straight from the theme is
     * code shipped by a developer, not content typed into the admin.
     *
     * @return list<list<string>>
     */
    public function sections(): array
    {
        $rows = [];
        foreach (Section::query()->where('path', 'like', 'sections_copy/%')->orderBy('path')->cursor() as $section) {
            $content = $this->sectionContent($section);
            if ($content === null) {
                $rows[] = [$section->path, $section->theme_uuid, 'file not found'];

                continue;
            }
            foreach ([...$this->scripts->scan($content), ...$this->syntax->scan($content)->manual] as $finding) {
                $rows[] = [$section->path, $section->theme_uuid, $finding];
            }
        }

        return $rows;
    }

    /**
     * @return iterable<array{0: EmailTemplate, 1: string, 2: string}>
     */
    private function templateFields(): iterable
    {
        foreach (EmailTemplate::query()->orderBy('name')->orderBy('locale')->cursor() as $template) {
            yield [$template, 'subject', (string) $template->subject];
            yield [$template, 'content', (string) $template->content];
        }
    }

    private function sectionContent(Section $section): ?string
    {
        try {
            return File::get(app('view')->getFinder()->find($section->path));
        } catch (\Throwable) {
            return null;
        }
    }
}

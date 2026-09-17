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

namespace App\Console\Commands;

use App\Models\Admin\EmailTemplate;
use App\Models\Personalization\Section;
use App\Services\Mail\LegacySyntaxReport;
use App\Services\Mail\LegacySyntaxScanner;
use App\Services\Personalization\SectionScriptScanner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AuditEditableContentCommand extends Command
{
    protected $signature = 'content:audit
                            {--details : List every item instead of only those needing a decision}';

    protected $description = 'Report which stored mail templates and edited theme sections survive the closed template grammar';

    private const STATUS_LABELS = [
        'clean' => 'nothing to do',
        'convertible' => 'converted automatically',
        'manual' => 'needs a decision',
    ];

    public function handle(LegacySyntaxScanner $scanner, SectionScriptScanner $scriptScanner): int
    {
        $rows = $this->scanTemplates($scanner);

        if ($rows === []) {
            $this->warn('No mail template stored. Nothing to audit.');
        } else {
            $this->summarise($rows);
            $this->detail($rows);
        }

        $sections = $this->reportSections($scanner, $scriptScanner);

        return $this->countByStatus($rows, 'manual') > 0 || $sections > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Edited sections only. A section still served straight from the theme is
     * code shipped by a developer, not content typed into the admin.
     *
     * @return int how many edited sections carry something that will change
     */
    private function reportSections(LegacySyntaxScanner $scanner, SectionScriptScanner $scriptScanner): int
    {
        $edited = Section::query()->where('path', 'like', 'sections_copy/%')->orderBy('path')->get();

        $this->newLine();
        $this->line('Theme sections');
        if ($edited->isEmpty()) {
            $this->info('No section has been edited from the admin.');

            return 0;
        }

        $rows = [];
        foreach ($edited as $section) {
            $content = $this->sectionContent($section);
            if ($content === null) {
                $rows[] = [$section->path, $section->theme_uuid, 'file not found'];

                continue;
            }
            foreach ([...$scriptScanner->scan($content), ...$scanner->scan($content)->manual] as $finding) {
                $rows[] = [$section->path, $section->theme_uuid, $finding];
            }
        }

        if ($rows === []) {
            $this->info(sprintf('%d edited section(s), nothing that will change.', $edited->count()));

            return 0;
        }

        $this->table(['Section', 'Theme', 'Finding'], $rows);

        return count($rows);
    }

    private function sectionContent(Section $section): ?string
    {
        try {
            return File::get(app('view')->getFinder()->find($section->path));
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return list<array{name: string, locale: string, field: string, report: LegacySyntaxReport}>
     */
    private function scanTemplates(LegacySyntaxScanner $scanner): array
    {
        $rows = [];
        foreach (EmailTemplate::query()->orderBy('name')->orderBy('locale')->cursor() as $template) {
            foreach (['subject' => $template->subject, 'content' => $template->content] as $field => $value) {
                $rows[] = [
                    'name' => $template->name,
                    'locale' => $template->locale,
                    'field' => $field,
                    'report' => $scanner->scan((string) $value),
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  list<array{name: string, locale: string, field: string, report: LegacySyntaxReport}>  $rows
     */
    private function summarise(array $rows): void
    {
        $this->newLine();
        $this->line('Mail templates');
        $this->table(
            ['Verdict', 'Items'],
            array_map(
                fn (string $status) => [self::STATUS_LABELS[$status], $this->countByStatus($rows, $status)],
                array_keys(self::STATUS_LABELS),
            ),
        );
    }

    /**
     * @param  list<array{name: string, locale: string, field: string, report: LegacySyntaxReport}>  $rows
     */
    private function detail(array $rows): void
    {
        $needing = array_filter($rows, fn (array $row) => $row['report']->needsManualWork());
        if ($needing === []) {
            $this->info('Every stored template converts on its own.');

            return;
        }

        $this->newLine();
        $this->line('Needing a decision, one line per construct:');
        $this->table(
            ['Template', 'Locale', 'Field', 'Construct'],
            array_merge(...array_map(
                fn (array $row) => array_map(
                    fn (string $construct) => [$row['name'], $row['locale'], $row['field'], $construct],
                    $row['report']->manual,
                ),
                array_values($needing),
            )),
        );

        $this->newLine();
        $this->line(sprintf('%d distinct constructs across %d items.', count(array_unique(array_merge(...array_map(fn (array $row) => $row['report']->manual, array_values($needing))))), count($needing)));
    }

    /**
     * @param  list<array{name: string, locale: string, field: string, report: LegacySyntaxReport}>  $rows
     */
    private function countByStatus(array $rows, string $status): int
    {
        return count(array_filter($rows, fn (array $row) => $row['report']->status() === $status));
    }
}

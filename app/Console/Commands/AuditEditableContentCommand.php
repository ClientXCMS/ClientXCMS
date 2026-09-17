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
use App\Services\Mail\LegacySyntaxReport;
use App\Services\Mail\LegacySyntaxScanner;
use Illuminate\Console\Command;

class AuditEditableContentCommand extends Command
{
    protected $signature = 'content:audit
                            {--details : List every item instead of only those needing a decision}';

    protected $description = 'Report which stored mail templates the closed template grammar accepts';

    private const STATUS_LABELS = [
        'clean' => 'nothing to do',
        'convertible' => 'converted automatically',
        'manual' => 'needs a decision',
    ];

    public function handle(LegacySyntaxScanner $scanner): int
    {
        $rows = $this->scanTemplates($scanner);

        if ($rows === []) {
            $this->warn('No mail template stored. Nothing to audit.');

            return self::SUCCESS;
        }

        $this->summarise($rows);
        $this->detail($rows);

        return $this->countByStatus($rows, 'manual') > 0 ? self::FAILURE : self::SUCCESS;
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

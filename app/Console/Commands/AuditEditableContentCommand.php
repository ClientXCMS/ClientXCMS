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

use App\Services\Content\EditableContentAudit;
use Illuminate\Console\Command;

class AuditEditableContentCommand extends Command
{
    protected $signature = 'content:audit';

    protected $description = 'Report which stored mail templates and edited theme sections survive the closed template grammar';

    private const STATUS_LABELS = [
        'clean' => 'nothing to do',
        'convertible' => 'converted automatically',
        'manual' => 'needs a decision',
    ];

    public function handle(EditableContentAudit $audit): int
    {
        $templates = $audit->templates();
        $this->reportTemplates($templates);

        $pending = $this->countByStatus($templates, 'manual')
            + $this->section(
                'Variables nothing will fill',
                ['Template', 'Locale', 'Field', 'Variable'],
                $audit->unknownVariables(),
                'Every variable used by a stored template is produced somewhere.',
            )
            + $this->section(
                'Mail settings',
                ['Setting', 'Finding'],
                $audit->settings(),
                'The opening and closing lines need nothing.',
            )
            + $this->section(
                'Theme sections',
                ['Section', 'Theme', 'Finding'],
                $audit->sections(),
                'No edited section carries anything that will change.',
            );

        return $pending > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     * @return int how many findings the section holds
     */
    private function section(string $title, array $headers, array $rows, string $emptyMessage): int
    {
        $this->newLine();
        $this->line($title);
        if ($rows === []) {
            $this->info($emptyMessage);

            return 0;
        }
        $this->table($headers, $rows);

        return count($rows);
    }

    /**
     * @param  list<array{name: string, locale: string, field: string, report: \App\Services\Mail\LegacySyntaxReport}>  $templates
     */
    private function reportTemplates(array $templates): void
    {
        if ($templates === []) {
            $this->warn('No mail template stored. Nothing to audit.');

            return;
        }

        $this->newLine();
        $this->line('Mail templates');
        $this->table(
            ['Verdict', 'Items'],
            array_map(
                fn (string $status) => [self::STATUS_LABELS[$status], $this->countByStatus($templates, $status)],
                array_keys(self::STATUS_LABELS),
            ),
        );

        $rows = $this->manualConstructRows($templates);
        if ($rows === []) {
            $this->info('Every stored template converts on its own.');

            return;
        }
        $this->table(['Template', 'Locale', 'Field', 'Construct'], $rows);
        $this->line(sprintf(
            '%d distinct constructs, %d occurrences, across %d fields.',
            count(array_unique(array_column($rows, 3))),
            count($rows),
            $this->countByStatus($templates, 'manual'),
        ));
    }

    /**
     * @param  list<array{name: string, locale: string, field: string, report: \App\Services\Mail\LegacySyntaxReport}>  $templates
     * @return list<list<string>>
     */
    private function manualConstructRows(array $templates): array
    {
        $rows = [];
        foreach ($templates as $template) {
            foreach ($template['report']->manual as $construct) {
                $rows[] = [$template['name'], $template['locale'], $template['field'], $construct];
            }
        }

        return $rows;
    }

    /**
     * @param  list<array{name: string, locale: string, field: string, report: \App\Services\Mail\LegacySyntaxReport}>  $templates
     */
    private function countByStatus(array $templates, string $status): int
    {
        return count(array_filter($templates, fn (array $row) => $row['report']->status() === $status));
    }
}

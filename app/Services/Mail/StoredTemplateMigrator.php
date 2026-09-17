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

namespace App\Services\Mail;

use App\Models\Admin\EmailTemplate;
use App\Models\Admin\Setting;

/**
 * Rewrites what an install already stores: the templates in database and the two
 * settings rendered like templates.
 *
 * Safe to run again. The converter leaves an already-converted template alone,
 * so a second pass reports zero changes rather than mangling the first one.
 */
class StoredTemplateMigrator
{
    /** Settings that are rendered like a template even though they are not one. */
    private const RENDERED_SETTINGS = ['mail_greeting', 'mail_salutation'];

    /**
     * Decisions, not guesses. Each line maps a construct the shipped templates
     * use to the field the matching prepared view now provides, so the templates
     * this project ships keep working without anyone editing them by hand.
     *
     * Deliberately exact strings: a pattern would reach into templates an
     * operator wrote, where the same expression may mean something else.
     */
    private const SHIPPED_REPLACEMENTS = [
        '{{ formatted_price($invoice->total, $invoice->currency) }}' => '{{ invoice.total }}',
        '{{ formatted_price($item->price(), $invoice->currency) }}' => '{{ price }}',
        "{{ \$ticket->department->trans('name') }}" => '{{ ticket.department }}',
        // The generic pass keeps Blade's camel case; the prepared views use snake case.
        '{{ ticket.customer.fullName }}' => '{{ ticket.customer.full_name }}',
    ];

    public function __construct(private readonly TemplateConverter $converter) {}

    /**
     * One template's worth of rewriting. Public because the seeder needs the same
     * treatment for templates an extension ships, which no migration ever sees.
     */
    public function rewrite(string $content): string
    {
        return $this->applyShippedReplacements($this->converter->convert($content)->after);
    }

    /**
     * @return array{changed: int, pending: list<array{0: string, 1: string}>}
     */
    public function migrate(bool $apply = true): array
    {
        $changed = 0;
        $pending = [];

        foreach (EmailTemplate::query()->cursor() as $template) {
            $updates = [];
            foreach (['subject', 'content'] as $field) {
                $before = (string) $template->{$field};
                $after = $this->applyShippedReplacements($this->converter->convert($before)->after);
                if ($after !== $before) {
                    $updates[$field] = $after;
                }
                foreach ($this->converter->convert($after)->untouched as $construct) {
                    $pending[] = [$template->name.' ('.$template->locale.') '.$field, $construct];
                }
            }
            if ($updates === []) {
                continue;
            }
            $changed += count($updates);
            if ($apply) {
                $template->forceFill($updates)->save();
            }
        }

        return ['changed' => $changed + $this->migrateSettings($apply, $pending), 'pending' => $pending];
    }

    private function applyShippedReplacements(string $content): string
    {
        return str_replace(array_keys(self::SHIPPED_REPLACEMENTS), array_values(self::SHIPPED_REPLACEMENTS), $content);
    }

    /**
     * @param  list<array{0: string, 1: string}>  $pending
     */
    private function migrateSettings(bool $apply, array &$pending): int
    {
        $changed = 0;
        foreach (self::RENDERED_SETTINGS as $key) {
            $conversion = $this->converter->convert((string) setting($key));
            foreach ($conversion->untouched as $construct) {
                $pending[] = ['setting '.$key, $construct];
            }
            if (! $conversion->changed()) {
                continue;
            }
            $changed++;
            if ($apply) {
                Setting::updateSettings([$key => $conversion->after], log: false);
            }
        }

        return $changed;
    }
}

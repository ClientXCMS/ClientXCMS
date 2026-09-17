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

namespace App\Rules;

use App\Services\Mail\LegacySyntaxScanner;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Refuses content the mail renderer would ship as literal text.
 *
 * Not a security control: the renderer executes nothing either way. This exists
 * so someone who types the old syntax learns it now, rather than from a customer
 * who received `{{ $customer->firstname }}` in their inbox.
 */
class RendersWithTemplateGrammar implements ValidationRule
{
    public function __construct(private readonly LegacySyntaxScanner $scanner) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $report = $this->scanner->scan($value);
        $found = [...$report->convertible, ...$report->manual];
        if ($found === []) {
            return;
        }

        // Full sentence as the key, like ValidHtmlWithoutBlade. A key added to
        // lang/fr is overwritten by translations:import, which CI runs before the
        // tests, so the message would come back raw.
        $fail(__('This text uses the old template syntax and would be sent as-is: :constructs', [
            'constructs' => implode(', ', array_slice($found, 0, 3)),
        ]));
    }
}

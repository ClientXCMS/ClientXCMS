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

/**
 * What a stored template still carries from the Blade syntax, split by whether
 * a machine can translate it or a human has to decide.
 */
final readonly class LegacySyntaxReport
{
    /**
     * @param  list<string>  $convertible  constructs a converter can translate on its own
     * @param  list<string>  $manual  constructs needing a decision, typically a prepared field
     */
    public function __construct(
        public array $convertible,
        public array $manual,
    ) {}

    public function isClean(): bool
    {
        return $this->convertible === [] && $this->manual === [];
    }

    public function needsManualWork(): bool
    {
        return $this->manual !== [];
    }

    public function status(): string
    {
        return match (true) {
            $this->needsManualWork() => 'manual',
            $this->isClean() => 'clean',
            default => 'convertible',
        };
    }
}

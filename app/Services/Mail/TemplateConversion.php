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
 * The outcome of rewriting one template: what it became, and what was left
 * alone because translating it would have been a guess.
 */
final readonly class TemplateConversion
{
    /**
     * @param  list<string>  $untouched  constructs a human still has to decide on
     */
    public function __construct(
        public string $before,
        public string $after,
        public array $untouched,
    ) {}

    public function changed(): bool
    {
        return $this->before !== $this->after;
    }

    public function isComplete(): bool
    {
        return $this->untouched === [];
    }
}

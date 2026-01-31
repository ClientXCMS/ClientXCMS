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

namespace App\Services\Account;

use Exception;

class AccountDeletionException extends Exception
{
    /**
     * The blocking reasons preventing deletion.
     */
    protected array $blockingReasons;

    public function __construct(string $message, array $blockingReasons = [], int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->blockingReasons = $blockingReasons;
    }

    /**
     * Get the blocking reasons.
     */
    public function getBlockingReasons(): array
    {
        return $this->blockingReasons;
    }

    /**
     * Get the formatted blocking reasons as a string.
     */
    public function getFormattedReasons(): string
    {
        return (new AccountDeletionService)->formatBlockingReasons($this->blockingReasons);
    }
}

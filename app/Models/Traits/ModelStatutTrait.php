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


namespace App\Models\Traits;

use Illuminate\Contracts\Database\Eloquent\Builder;

trait ModelStatutTrait
{
    /**
     * @var array|string[] - Types of status
     *                     Active : visible and can be used for everyone
     *                     Hidden : visible but can't be used for everyone
     *                     Unreferenced : not visible and can be used for admin only
     */
    public array $statusList = [
        'active',
        'hidden',
        'unreferenced',
    ];

    public function isValid(bool $canUnreferenced = false)
    {
        if ($this->status == 'active') {
            return true;
        }
        if ($canUnreferenced && $this->status == 'unreferenced') {
            return true;
        }

        return false;
    }

    public function isNotValid(bool $canUnreferenced = false)
    {
        return ! $this->isValid($canUnreferenced);
    }

    public function switchStatus(string $status)
    {
        $this->status = $status;
        $this->save();
    }

    public static function getAvailable(bool $inAdmin = false): Builder
    {
        if ($inAdmin) {
            return self::query()->whereIn('status', ['active', 'unreferenced'], 'or');
        }

        return self::where('status', 'active', 'or');
    }
}

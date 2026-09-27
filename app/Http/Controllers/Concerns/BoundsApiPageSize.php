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

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait BoundsApiPageSize
{
    protected const MAX_PAGE_SIZE = 100;

    protected function boundedPageSize(Request $request, int $default): int
    {
        $perPage = filter_var($request->input('per_page'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return min($perPage === false ? $default : $perPage, self::MAX_PAGE_SIZE);
    }
}

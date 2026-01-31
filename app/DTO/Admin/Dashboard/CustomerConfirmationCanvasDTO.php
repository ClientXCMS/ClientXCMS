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

namespace App\DTO\Admin\Dashboard;

use Illuminate\Support\Collection;

class CustomerConfirmationCanvasDTO implements \App\Contracts\CanvasDTOInterface
{
    public Collection $items;

    public function __construct(Collection $items)
    {
        $this->items = $items;
    }

    public function getLabels()
    {
        return json_encode([[__('global.states.confirmed'), __('global.states.not_confirmed')]]);
    }

    public function getColors()
    {
        return json_encode(['#00a65a', '#f56954']);
    }

    public function isEmpty()
    {
        return $this->items->isEmpty();
    }

    public function getValues()
    {
        $confirmed = $this->items->where('is_confirmed', true)->first();
        $notConfirmed = $this->items->where('is_confirmed', false)->first();
        if ($confirmed === null) {
            $confirmed = 0;
        } else {
            $confirmed = $confirmed->count;
        }
        if ($notConfirmed === null) {
            $notConfirmed = 0;
        } else {
            $notConfirmed = $notConfirmed->count;
        }

        return json_encode([[$confirmed, $notConfirmed]]);
    }

    public function getTitles()
    {
        return json_encode(['']);
    }
}

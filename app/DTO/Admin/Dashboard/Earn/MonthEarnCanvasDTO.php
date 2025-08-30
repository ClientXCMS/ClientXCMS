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


namespace App\DTO\Admin\Dashboard\Earn;

use Illuminate\Support\Collection;

class MonthEarnCanvasDTO implements \App\Contracts\CanvasDTOInterface
{
    private Collection $items;

    private ?Collection $compared = null;

    private ?int $comparedYear;

    public function __construct(Collection $items, ?Collection $compared = null, ?int $comparedYear = null)
    {
        $this->items = $items;
        $this->compared = $compared;
        $this->comparedYear = $comparedYear;
    }

    public function getLabels()
    {
        if ($this->compared) {
            return collect([$this->items->map(function ($item) {
                return $item['month'];
            })->values(), $this->compared->map(function ($item) {
                return $item['month'];
            })->values()])->toJson();
        }

        return collect([$this->items->map(function ($item) {
            return $item['month'];
        })->values()])->toJson();
    }

    public function getColors()
    {
        return $this->items->map(function ($item) {
            return '#'.substr(md5($item['month']), 0, 6);
        })->values()->toJson();
    }

    public function isEmpty()
    {
        return count($this->items) == 0;
    }

    public function getValues()
    {
        if ($this->compared) {
            return collect([$this->items->map(function ($item) {
                return $item['total'];
            })->values(), $this->compared->map(function ($item) {
                return $item['total'];
            })->values()])->toJson();
        }

        return collect([$this->items->map(function ($item) {
            return $item['total'];
        })])->values()->toJSON();
    }

    public function getTitles()
    {
        if ($this->compared) {
            return collect([now()->year, $this->comparedYear])->toJson();
        }

        return collect([now()->year])->toJson();
    }
}

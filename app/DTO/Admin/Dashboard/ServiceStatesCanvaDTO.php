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
 * Year: 2025
 */
namespace App\DTO\Admin\Dashboard;

use App\Contracts\CanvasDTOInterface;

class ServiceStatesCanvaDTO implements CanvasDTOInterface
{
    public array $items;

    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function getPendings()
    {
        $item = collect($this->items)->firstWhere('status', 'pending');

        return $item ? $item['count'] : 0;
    }

    public function getActives()
    {
        $item = collect($this->items)->firstWhere('status', 'active');

        return $item ? $item['count'] : 0;
    }

    public function getExpireds()
    {
        $item = collect($this->items)->firstWhere('status', 'expired');

        return $item ? $item['count'] : 0;
    }

    public function getCancelleds()
    {
        $item = collect($this->items)->firstWhere('status', 'cancelled');

        return $item ? $item['count'] : 0;
    }

    public function getSuspendeds()
    {
        $item = collect($this->items)->firstWhere('status', 'suspended');

        return $item ? $item['count'] : 0;
    }

    public function getLabels()
    {
        return json_encode([collect($this->getNotZeroValues())->map(fn ($status) => __('global.states.'.$status))->values()]);
    }

    public function getColors()
    {
        $colors = [
            'pending' => '#9f9f9f',
            'active' => '#00a65a',
            'expired' => '#f56954',
            'cancelled' => '#f39c12',
            'suspended' => '#f39c12',
        ];

        return collect($this->getNotZeroValues())->map(fn ($status) => $colors[$status])->values()->toJson();
    }

    public function isEmpty()
    {
        return collect($this->items)->isEmpty();
    }

    public function getValues()
    {
        return json_encode([collect($this->getNotZeroValues())->map(fn ($status) => $this->{'get'.ucfirst($status).'s'}())->values()]);
    }

    private function getNotZeroValues()
    {
        return collect(['pending', 'active', 'expired', 'cancelled', 'suspended'])->filter(fn ($status) => $this->{'get'.ucfirst($status).'s'}() > 0);
    }

    public function getTitles()
    {
        return json_encode(['']);
    }
}

<?php

namespace App\Services\Billing;

class FiscalProfileExtensionRegistry
{
    private array $views = [];

    public function add(string $key, string $view): void
    {
        $this->views[$key] = $view;
    }

    public function views(): array
    {
        return $this->views;
    }
}

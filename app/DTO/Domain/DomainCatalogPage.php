<?php

namespace App\DTO\Domain;

/** Prices are total standard reseller costs for the explicit duration, never premium quotes. */
final class DomainCatalogPage
{
    /** @param array<int,array{extension:string,action:string,billing:string,cost:float,currency:string}> $prices */
    public function __construct(public array $prices, public ?int $nextOffset, public ?int $total = null) {}
}

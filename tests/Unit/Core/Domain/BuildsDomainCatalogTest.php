<?php

namespace Tests\Unit\Core\Domain;

use App\Core\Domain\Concerns\BuildsDomainCatalog;
use Tests\TestCase;

class BuildsDomainCatalogTest extends TestCase
{
    public function test_valid_rows_are_normalized(): void
    {
        $row = $this->catalog()->call('catalogRow', 'COM', 'register', '8.99', 'usd');

        $this->assertSame(['extension' => '.com', 'action' => 'register', 'billing' => 'annually', 'cost' => 8.99, 'currency' => 'USD'], $row);
    }

    public function test_rows_the_catalog_job_would_reject_are_dropped(): void
    {
        $catalog = $this->catalog();
        $invalid = [
            ['.com', 'restore', 8.99, 'USD'],
            ['.com', 'register', 0, 'USD'],
            ['.com', 'register', -1, 'USD'],
            ['.com', 'register', 'n/a', 'USD'],
            ['.com', 'register', 8.99, 'TL'],
            ['.com', 'register', 8.99, ''],
            ['.co m', 'register', 8.99, 'USD'],
            ['.'.str_repeat('a', 40), 'register', 8.99, 'USD'],
        ];
        foreach ($invalid as [$extension, $action, $cost, $currency]) {
            $this->assertNull($catalog->call('catalogRow', $extension, $action, $cost, $currency), json_encode([$extension, $action, $cost, $currency]));
        }
    }

    public function test_single_page_catalogs_stop_after_the_first_page(): void
    {
        $catalog = $this->catalog();
        $rows = [$catalog->call('catalogRow', '.com', 'register', 9, 'EUR'), null];

        $first = $catalog->call('singleCatalogPage', $rows, 0);
        $next = $catalog->call('singleCatalogPage', $rows, 100);

        $this->assertCount(1, $first->prices);
        $this->assertNull($first->nextOffset);
        $this->assertSame([], $next->prices);
        $this->assertNull($next->nextOffset);
    }

    public function test_extension_filter_accepts_any_dot_and_case(): void
    {
        $catalog = $this->catalog();
        $rows = [
            $catalog->call('catalogRow', '.com', 'register', 9, 'EUR'),
            $catalog->call('catalogRow', '.net', 'register', 10, 'EUR'),
            $catalog->call('catalogRow', '.fr', 'renew', 7, 'EUR'),
        ];

        $filtered = $catalog->call('onlyExtensions', $rows, ['COM', '.fr']);

        $this->assertSame(['.com', '.fr'], array_column($filtered, 'extension'));
    }

    private function catalog(): object
    {
        return new class
        {
            use BuildsDomainCatalog;

            public function call(string $method, mixed ...$arguments): mixed
            {
                return $this->{$method}(...$arguments);
            }
        };
    }
}

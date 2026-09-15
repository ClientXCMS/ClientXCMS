<?php

namespace Tests\Unit\Models\Store;

use App\Models\Store\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDescriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_parses_legacy_short_descriptions(): void
    {
        $items = Product::parseProductDescription('v Inclus[--]x Exclu[--]Information');

        $this->assertSame(['bi bi-check-lg', 'bi bi-x-lg', null], array_column($items, 'icon'));
        $this->assertSame(['Inclus', 'Exclu', 'Information'], array_column($items, 'text'));
    }

    public function test_it_stores_structured_items_and_localized_text_with_fallback(): void
    {
        $product = Product::factory()->create();
        $product->syncProductDescriptions([
            ['id' => 'cpu', 'icon' => 'bi bi-cpu', 'text' => 'Processeur rapide'],
            ['id' => 'support', 'icon' => '', 'text' => 'Support inclus'],
        ], [
            'en_GB' => ['cpu' => 'Fast processor'],
        ]);

        $stored = json_decode($product->fresh()->getMetadata('product_description'), true);
        $this->assertSame(2, $stored['version']);
        $this->assertSame('bi bi-cpu', $stored['items'][0]['icon']);

        $localized = $product->fresh()->getProductDescriptionItems('en_GB');
        $this->assertSame('Fast processor', $localized[0]['text']);
        $this->assertSame('Support inclus', $localized[1]['text']);
    }

    public function test_it_rejects_invalid_icons_and_clears_empty_descriptions(): void
    {
        $product = Product::factory()->create();
        $product->syncProductDescriptions([
            ['id' => 'unsafe', 'icon' => 'bi bi-check" onclick="alert(1)', 'text' => 'Sécurisé'],
        ]);

        $this->assertNull($product->fresh()->getProductDescriptionItems('en_GB')[0]['icon']);

        $product->syncProductDescriptions([]);
        $this->assertNull($product->fresh()->getMetadata('product_description'));
        $this->assertDatabaseMissing('translations', [
            'model' => Product::class,
            'model_id' => $product->id,
            'key' => 'product_description',
        ]);
    }

    public function test_formatted_description_lines_converts_html_without_changing_it(): void
    {
        $html = '<p>Introduction</p><ul><li>Premier</li><li>Second</li></ul>';
        $product = Product::factory()->make(['description' => $html]);

        $this->assertSame(['Introduction', 'Premier', 'Second'], $product->formattedDescriptionLines());
        $this->assertSame($html, $product->description);
    }

    public function test_a_product_without_description_formats_to_an_empty_string(): void
    {
        $product = new Product;

        $this->assertSame('', $product->formattedDescription());
        $this->assertSame([], $product->formattedDescriptionLines());
    }
}

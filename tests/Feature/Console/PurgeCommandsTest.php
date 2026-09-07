<?php

namespace Tests\Feature\Console;

use App\Models\Account\Customer;
use App\Models\Metadata;
use App\Models\Store\Basket\Basket;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PurgeCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_purge_basket_deletes_each_old_anonymous_basket_once(): void
    {
        Carbon::setTestNow('2026-08-25 12:00:00');
        $old = $this->basket(now()->subWeeks(2)->subSecond());
        $atBoundary = $this->basket(now()->subWeeks(2));
        $recent = $this->basket(now()->subWeek());
        $completed = $this->basket(now()->subMonth(), ['completed_at' => now()->subDay()]);
        $identified = $this->basket(now()->subMonth(), ['user_id' => Customer::factory()->create()->id]);

        $this->artisan('clientxcms:purge-basket', ['batchSize' => 10])
            ->expectsOutputToContain('Found 1 basket records to purge.')
            ->expectsOutputToContain('Purged 1 basket records.')
            ->assertExitCode(Command::SUCCESS);

        $this->assertModelMissing($old);
        $this->assertModelExists($atBoundary);
        $this->assertModelExists($recent);
        $this->assertModelExists($completed);
        $this->assertModelExists($identified);
    }

    public function test_purge_metadata_removes_orphans_and_keeps_valid_relations(): void
    {
        $customer = Customer::factory()->create();
        $valid = Metadata::create([
            'model_type' => Customer::class,
            'model_id' => $customer->id,
            'key' => 'valid',
            'value' => '1',
        ]);
        $missingRecord = Metadata::create([
            'model_type' => Customer::class,
            'model_id' => PHP_INT_MAX,
            'key' => 'missing',
            'value' => '1',
        ]);
        $missingClass = Metadata::create([
            'model_type' => 'App\\Models\\MissingModel',
            'model_id' => 1,
            'key' => 'missing-class',
            'value' => '1',
        ]);

        $this->artisan('clientxcms:purge-metadata', ['batchSize' => 1])
            ->assertExitCode(Command::SUCCESS);

        $this->assertModelExists($valid);
        $this->assertModelMissing($missingRecord);
        $this->assertModelMissing($missingClass);
    }

    private function basket(Carbon $createdAt, array $attributes = []): Basket
    {
        $basket = Basket::create(array_merge([
            'uuid' => (string) Str::uuid(),
            'ip_address' => '127.0.0.1',
        ], $attributes));
        $basket->timestamps = false;
        $basket->created_at = $createdAt;
        $basket->updated_at = $createdAt;
        $basket->save();

        return $basket;
    }
}

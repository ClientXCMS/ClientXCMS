<?php

namespace Tests\Feature\Admin\Personalization;

use App\Models\Personalization\SocialNetwork;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialCrudControllerTest extends TestCase
{
    use RefreshDatabase;

    const ROUTE_PREFIX = 'admin/personalization/socials';

    private function createSocial(array $attributes = []): SocialNetwork
    {
        return SocialNetwork::create(array_merge([
            'name' => 'Social ' . rand(1, 9999),
            'icon' => 'bi bi-github',
            'url' => 'https://example.com',
            'position' => 0,
        ], $attributes));
    }

    public function test_index_returns_200(): void
    {
        $response = $this->performAdminAction('GET', self::ROUTE_PREFIX);
        $response->assertStatus(200);
    }

    public function test_index_displays_socials_ordered_by_position(): void
    {
        $socialC = $this->createSocial(['name' => 'C', 'position' => 2]);
        $socialA = $this->createSocial(['name' => 'A', 'position' => 0]);
        $socialB = $this->createSocial(['name' => 'B', 'position' => 1]);

        $response = $this->performAdminAction('GET', self::ROUTE_PREFIX);
        $response->assertStatus(200);

        $items = $response->original->getData()['items'];
        $names = $items->pluck('name')->toArray();
        $this->assertEquals(['A', 'B', 'C'], $names);
    }

    public function test_index_requires_permission(): void
    {
        $response = $this->performAdminAction('GET', self::ROUTE_PREFIX, [], ['admin.manage_products']);
        $response->assertStatus(403);
    }

    public function test_store_sets_position_auto_increment(): void
    {
        $this->createSocial(['position' => 0]);
        $this->createSocial(['position' => 1]);

        $response = $this->performAdminAction('POST', self::ROUTE_PREFIX, [
            'name' => 'New Social',
            'icon' => 'bi bi-twitter',
            'url' => 'https://twitter.com',
        ]);

        $response->assertStatus(302);

        $latest = SocialNetwork::orderByDesc('id')->first();
        $this->assertEquals('New Social', $latest->name);
        $this->assertEquals(2, $latest->position);
    }

    public function test_store_first_social_gets_position_one(): void
    {
        $response = $this->performAdminAction('POST', self::ROUTE_PREFIX, [
            'name' => 'First Social',
            'icon' => 'bi bi-github',
            'url' => 'https://github.com',
        ]);

        $response->assertStatus(302);

        $social = SocialNetwork::first();
        $this->assertEquals(1, $social->position);
    }

    public function test_sort_reorders_socials(): void
    {
        $social1 = $this->createSocial(['position' => 0]);
        $social2 = $this->createSocial(['position' => 1]);
        $social3 = $this->createSocial(['position' => 2]);

        $response = $this->performAdminAction('POST', self::ROUTE_PREFIX . '/sort', [
            'items' => [$social3->id, $social1->id, $social2->id],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $social1->refresh();
        $social2->refresh();
        $social3->refresh();

        $this->assertEquals(1, $social1->position);
        $this->assertEquals(2, $social2->position);
        $this->assertEquals(0, $social3->position);
    }

    public function test_sort_requires_permission(): void
    {
        $social = $this->createSocial();

        $response = $this->performAdminAction(
            'POST',
            self::ROUTE_PREFIX . '/sort',
            ['items' => [$social->id]],
            ['admin.show_theme_socialnetworks']
        );

        $response->assertStatus(403);
    }

    public function test_sort_validates_items_required(): void
    {
        $response = $this->performAdminAction('POST', self::ROUTE_PREFIX . '/sort', []);
        $response->assertStatus(422);
    }

    public function test_sort_validates_items_are_integers(): void
    {
        $response = $this->performAdminAction('POST', self::ROUTE_PREFIX . '/sort', [
            'items' => ['abc', 'def'],
        ]);
        $response->assertStatus(422);
    }

    public function test_sort_validates_items_exist_in_database(): void
    {
        $response = $this->performAdminAction('POST', self::ROUTE_PREFIX . '/sort', [
            'items' => [99999, 99998],
        ]);
        $response->assertStatus(422);
    }
}

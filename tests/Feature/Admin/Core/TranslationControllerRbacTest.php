<?php

namespace Tests\Feature\Admin\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationControllerRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_translations_blocks_admin_without_permission(): void
    {
        $response = $this->performAdminAction(
            'POST',
            route('admin.translations.index'),
            [
                'translations' => ['fr_FR' => ['key' => 'pentest tampered']],
                'model' => 'App\\Models\\Personalization\\Section',
                'model_id' => 1,
            ],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_store_settings_translations_blocks_admin_without_permission(): void
    {
        $response = $this->performAdminAction(
            'POST',
            route('admin.translations.settings'),
            [
                'translations' => ['fr_FR' => ['app_name' => 'PWNED']],
            ],
            ['admin.show_invoices']
        );
        $response->assertStatus(403);
    }

    public function test_store_translations_allows_admin_with_permission(): void
    {
        $response = $this->performAdminAction(
            'POST',
            route('admin.translations.index'),
            [
                'translations' => ['fr_FR' => ['key' => 'legit']],
                'model' => 'App\\Models\\Personalization\\Section',
                'model_id' => 1,
            ],
            ['admin.manage_personalization']
        );
        $this->assertNotEquals(403, $response->status(), 'Admin with MANAGE_PERSONALIZATION must not be blocked');
    }
}

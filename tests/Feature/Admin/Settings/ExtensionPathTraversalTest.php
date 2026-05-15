<?php

namespace Tests\Feature\Admin\Settings;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtensionPathTraversalTest extends TestCase
{
    use RefreshDatabase;

    public function test_enable_rejects_extension_with_path_traversal(): void
    {
        $response = $this->performAdminAction(
            'POST',
            '/admin/settings/extensions/modules/'.rawurlencode('../storage/app/public/exploit').'/enable',
            [],
            ['admin.manage_extensions']
        );
        $this->assertContains($response->status(), [400, 404, 405], 'Path traversal payload must be rejected before reaching Artisan migrate');
    }

    public function test_disable_rejects_extension_with_path_traversal(): void
    {
        $response = $this->performAdminAction(
            'POST',
            '/admin/settings/extensions/modules/'.rawurlencode('../storage/app/public/exploit').'/disable',
            [],
            ['admin.manage_extensions']
        );
        $this->assertContains($response->status(), [400, 404, 405]);
    }

    public function test_uninstall_rejects_extension_with_path_traversal(): void
    {
        $response = $this->performAdminAction(
            'DELETE',
            '/admin/settings/extensions/modules/'.rawurlencode('../storage/app/public/exploit').'/uninstall',
            [],
            ['admin.manage_extensions']
        );
        $this->assertContains($response->status(), [400, 404, 405]);
    }

    public function test_bulk_action_rejects_extension_uuid_with_path_traversal(): void
    {
        $response = $this->performAdminAction(
            'POST',
            '/admin/settings/extensions/bulk',
            [
                'action' => 'enable',
                'extensions' => [
                    ['type' => 'modules', 'uuid' => '../storage/app/public/exploit'],
                ],
            ],
            ['admin.manage_extensions']
        );
        $response->assertStatus(422);
    }

    public function test_validate_extension_identifier_accepts_legit_names(): void
    {
        $controller = new \App\Http\Controllers\Admin\Settings\SettingsExtensionController;
        $reflection = new \ReflectionMethod($controller, 'validateExtensionIdentifier');
        $reflection->setAccessible(true);

        $reflection->invoke($controller, 'modules', 'service_bundles');
        $reflection->invoke($controller, 'addons', 'support-id');
        $reflection->invoke($controller, 'themes', 'cerbonix');
        $this->assertTrue(true, 'Legit identifiers must pass without throwing');
    }
}

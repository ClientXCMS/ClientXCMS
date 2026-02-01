<?php

namespace Tests\Feature\Extensions;

use App\Extensions\ExtensionManager;
use App\Models\Admin\Admin;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtensionAjaxActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ExtensionManager::writeExtensionJson([]);
    }

    private function seedAndGetAdmin(): Admin
    {
        $this->seed(AdminSeeder::class);

        return Admin::first();
    }

    // Enable succeeds through ExtensionManager but getExtension() fails because
    // the API (makeRequest) returns empty in test env. This tests the JSON error path.
    public function test_enable_returns_json_error_when_extension_not_in_api(): void
    {
        $admin = $this->seedAndGetAdmin();

        ExtensionManager::writeExtensionJson([
            'modules' => [
                ['uuid' => 'test-mod', 'version' => '1.0.0', 'type' => 'modules', 'enabled' => false, 'installed' => true, 'api' => []],
            ],
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.settings.extensions.enable', ['type' => 'modules', 'extension' => 'test-mod']));

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => ['extension'],
            'errors',
        ]);
    }

    public function test_enable_returns_redirect_when_not_ajax(): void
    {
        $admin = $this->seedAndGetAdmin();

        ExtensionManager::writeExtensionJson([
            'modules' => [
                ['uuid' => 'test-mod', 'version' => '1.0.0', 'type' => 'modules', 'enabled' => false, 'installed' => true, 'api' => []],
            ],
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.extensions.enable', ['type' => 'modules', 'extension' => 'test-mod']));

        $response->assertRedirect();
    }

    public function test_disable_returns_json_when_ajax(): void
    {
        $admin = $this->seedAndGetAdmin();

        ExtensionManager::writeExtensionJson([
            'modules' => [
                ['uuid' => 'test-mod', 'version' => '1.0.0', 'type' => 'modules', 'enabled' => true, 'installed' => true, 'api' => []],
            ],
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.settings.extensions.disable', ['type' => 'modules', 'extension' => 'test-mod']));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'errors' => [],
        ]);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => ['extension'],
            'errors',
        ]);
    }

    public function test_disable_returns_redirect_when_not_ajax(): void
    {
        $admin = $this->seedAndGetAdmin();

        ExtensionManager::writeExtensionJson([
            'modules' => [
                ['uuid' => 'test-mod', 'version' => '1.0.0', 'type' => 'modules', 'enabled' => true, 'installed' => true, 'api' => []],
            ],
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.extensions.disable', ['type' => 'modules', 'extension' => 'test-mod']));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_update_returns_json_when_ajax(): void
    {
        $admin = $this->seedAndGetAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.settings.extensions.update', ['type' => 'modules', 'extension' => 'test-mod']));

        $response->assertJsonStructure([
            'success',
            'message',
            'data',
            'errors',
        ]);
        $this->assertFalse($response->json('success'));
    }

    public function test_enable_rejects_invalid_type(): void
    {
        $admin = $this->seedAndGetAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.settings.extensions.enable', ['type' => 'invalid', 'extension' => 'test-mod']));

        $response->assertStatus(404);
    }

    public function test_disable_rejects_invalid_type(): void
    {
        $admin = $this->seedAndGetAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.settings.extensions.disable', ['type' => 'invalid', 'extension' => 'test-mod']));

        $response->assertStatus(404);
    }

    public function test_update_rejects_invalid_type(): void
    {
        $admin = $this->seedAndGetAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.settings.extensions.update', ['type' => 'invalid', 'extension' => 'test-mod']));

        $response->assertStatus(404);
    }

    // Unauthenticated requests are blocked by the admin middleware (401)
    // before reaching staff_aborts_permission (which would return 403).
    public function test_enable_requires_authentication(): void
    {
        $response = $this->postJson(route('admin.settings.extensions.enable', ['type' => 'modules', 'extension' => 'test']));

        $response->assertStatus(401);
    }

    public function test_disable_requires_authentication(): void
    {
        $response = $this->postJson(route('admin.settings.extensions.disable', ['type' => 'modules', 'extension' => 'test']));

        $response->assertStatus(401);
    }

    public function test_update_requires_authentication(): void
    {
        $response = $this->postJson(route('admin.settings.extensions.update', ['type' => 'modules', 'extension' => 'test']));

        $response->assertStatus(401);
    }

    public function test_json_response_has_uniform_contract(): void
    {
        $admin = $this->seedAndGetAdmin();

        ExtensionManager::writeExtensionJson([
            'modules' => [
                ['uuid' => 'test-mod', 'version' => '1.0.0', 'type' => 'modules', 'enabled' => true, 'installed' => true, 'api' => []],
            ],
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.settings.extensions.disable', ['type' => 'modules', 'extension' => 'test-mod']));

        $response->assertOk();
        $json = $response->json();

        $this->assertArrayHasKey('success', $json);
        $this->assertArrayHasKey('message', $json);
        $this->assertArrayHasKey('data', $json);
        $this->assertArrayHasKey('errors', $json);
        $this->assertIsBool($json['success']);
        $this->assertIsString($json['message']);
        $this->assertIsArray($json['data']);
        $this->assertIsArray($json['errors']);
    }

    public function test_install_route_exists_and_requires_authentication(): void
    {
        $response = $this->postJson(route('admin.settings.extensions.install', ['type' => 'modules', 'uuid' => 'test']));

        $response->assertStatus(401);
    }

    // install() calls update() which hits the API (empty in test env), so this
    // tests the JSON error path. Verifies uniform contract on download failure.
    public function test_install_returns_json_error_when_extension_not_in_api(): void
    {
        $admin = $this->seedAndGetAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.settings.extensions.install', ['type' => 'modules', 'uuid' => 'test-mod']));

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
            'errors',
        ]);
    }

    public function test_install_rejects_invalid_type(): void
    {
        $admin = $this->seedAndGetAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.settings.extensions.install', ['type' => 'invalid', 'uuid' => 'test-mod']));

        $response->assertStatus(404);
    }

    /**
     * H3/H1: install with activate=false should still return uniform JSON contract.
     * In test env the API is empty so update() fails, but the error path
     * must remain consistent regardless of the activate parameter.
     */
    public function test_install_with_activate_false_returns_json_error(): void
    {
        $admin = $this->seedAndGetAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->postJson(
                route('admin.settings.extensions.install', ['type' => 'modules', 'uuid' => 'test-mod']),
                ['activate' => false]
            );

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
            'errors',
        ]);
    }

    /**
     * H3: Default install behavior (activate=true) must stay backward-compatible.
     * Without explicit activate parameter, the controller defaults to activate=true.
     */
    public function test_install_defaults_to_activate_true(): void
    {
        $admin = $this->seedAndGetAdmin();

        // Without activate parameter - should behave as original (activate=true)
        $responseDefault = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.settings.extensions.install', ['type' => 'modules', 'uuid' => 'test-mod']));

        // With activate=true explicitly
        $responseExplicit = $this->actingAs($admin, 'admin')
            ->postJson(
                route('admin.settings.extensions.install', ['type' => 'modules', 'uuid' => 'test-mod']),
                ['activate' => true]
            );

        // Both should fail the same way (API empty in test env)
        $this->assertEquals($responseDefault->status(), $responseExplicit->status());
        $this->assertEquals($responseDefault->json('success'), $responseExplicit->json('success'));
    }

    /**
     * H3: Verify activate parameter does not affect type validation.
     * Invalid type must still return 404 regardless of activate value.
     */
    public function test_install_with_activate_false_still_rejects_invalid_type(): void
    {
        $admin = $this->seedAndGetAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->postJson(
                route('admin.settings.extensions.install', ['type' => 'invalid', 'uuid' => 'test-mod']),
                ['activate' => false]
            );

        $response->assertStatus(404);
    }
}

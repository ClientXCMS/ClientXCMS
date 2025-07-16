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
namespace Tests\Feature\Api\Application\Store;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupControllerTest extends TestCase
{
    const API_URL = 'api/application/groups';

    const ABILITY_INDEX = 'groups:index';

    const ABILITY_STORE = 'groups:store';

    const ABILITY_SHOW = 'groups:show';

    const ABILITY_UPDATE = 'groups:update';

    const ABILITY_DELETE = 'groups:delete';

    use RefreshDatabase;

    public function test_api_application_group_index(): void
    {
        $response = $this->performAction('GET', self::API_URL, [self::ABILITY_INDEX]);
        $response->assertStatus(200);
    }

    public function test_api_application_group_store(): void
    {
        $response = $this->performAction('POST', self::API_URL, [self::ABILITY_STORE], [
            'name' => 'Test Group',
            'description' => 'Test Group',
            'slug' => 'test-group',
            'status' => 'active',
            'pinned' => false,
            'sort_order' => 1,
            'parent_id' => null,
        ]);
        $response->assertStatus(201);
    }

    public function test_api_application_group_store_with_invalid_group(): void
    {
        $response = $this->performAction('POST', self::API_URL, [self::ABILITY_STORE], [
            'name' => 'Test Group',
            'description' => 'Test Group',
            'slug' => 'test-group',
            'status' => 'active',
            'pinned' => false,
            'sort_order' => 1,
            'group' => -1,
        ]);
        $response->assertStatus(201);
    }

    public function test_api_application_group_get(): void
    {
        $id = $this->createGroupModel()->id;
        $response = $this->performAction('GET', self::API_URL.'/'.$id, [self::ABILITY_SHOW]);
        $response->assertStatus(200);
    }

    public function test_api_application_group_delete(): void
    {
        $id = $this->createGroupModel()->id;

        $response = $this->performAction('DELETE', self::API_URL.'/'.$id, [self::ABILITY_DELETE]);
        $response->assertStatus(200);
    }

    public function test_api_application_group_update(): void
    {
        $id = $this->createGroupModel()->id;
        $response = $this->performAction('POST', self::API_URL.'/'.$id, [self::ABILITY_UPDATE], [
            'name' => 'New Product name',
            'pinned' => true,
            'parent_id' => null,
        ]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'New Product name', 'pinned' => true]);
    }

    public function test_api_application_change_status_group_invalid(): void
    {
        $id = $this->createGroupModel()->id;
        $response = $this->performAction('POST', self::API_URL.'/'.$id, [self::ABILITY_UPDATE], [
            'status' => 'bad',
            'pinned' => true,
        ]);
        $response->assertStatus(422);
    }

    public function test_api_application_change_status_group_valid(): void
    {
        $id = $this->createGroupModel()->id;
        $response = $this->performAction('POST', self::API_URL.'/'.$id, [self::ABILITY_UPDATE], [
            'status' => 'hidden',
            'pinned' => true,
            'parent_id' => null,
        ]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'hidden', 'pinned' => true]);
    }
}

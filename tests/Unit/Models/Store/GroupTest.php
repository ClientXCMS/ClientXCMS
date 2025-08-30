<?php

namespace Tests\Unit\Models\Store;

use Tests\TestCase;

class GroupTest extends TestCase
{
    public function test_group_has_default_route()
    {
        $group = $this->createGroupModel();
        $this->assertEquals(route('front.store.group', ['group' => $group->slug]), $group->route());
    }

    public function test_group_has_custom_route()
    {
        $group = $this->createGroupModel();
        $group->attachMetadata('group_url', 'custom-route');
        $group->save();
        $this->assertEquals('custom-route', $group->route(true));
    }

    public function test_has_parent_group()
    {
        $group = $this->createGroupModel();
        $subGroup = $this->createGroupModel('active', $group->id);
        $group->groups()->save($subGroup);
        $this->assertTrue($subGroup->isSubgroup());
        $this->assertFalse($subGroup->isGroup());
        $this->assertEquals(route('front.store.subgroup', ['group' => $group->slug, 'subgroup' => $subGroup->slug]), $subGroup->route(true));
    }
}

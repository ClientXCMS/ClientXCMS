<?php

namespace Tests\Feature\Profile;

use App\Models\Account\Customer;
use App\Models\Admin\Admin;
use App\Services\Account\AvatarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * v2.16 — verifies the avatar upload/delete pipeline introduced for
 * Customer accounts.
 */
class AvatarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_authenticated_customer_can_upload_an_avatar(): void
    {
        /** @var Customer $customer */
        $customer = Customer::factory()->create();
        $file = UploadedFile::fake()->image('me.png', 600, 400);

        $response = $this->actingAs($customer, 'web')
            ->post(route('front.profile.avatar.upload'), ['avatar' => $file]);

        $response->assertRedirect(route('front.profile.index'));
        $customer->refresh();
        $this->assertNotNull($customer->avatar_path);
        Storage::disk('public')->assertExists($customer->avatar_path);
    }

    public function test_avatar_upload_rejects_non_image_payload(): void
    {
        $customer = Customer::factory()->create();
        $bogus = UploadedFile::fake()->create('not-an-image.pdf', 100, 'application/pdf');

        $response = $this->actingAs($customer, 'web')
            ->post(route('front.profile.avatar.upload'), ['avatar' => $bogus]);

        $response->assertSessionHasErrors('avatar');
        $customer->refresh();
        $this->assertNull($customer->avatar_path);
    }

    public function test_customer_can_delete_their_avatar(): void
    {
        $customer = Customer::factory()->create();
        AvatarService::upload($customer, UploadedFile::fake()->image('me.png', 100, 100));
        $customer->refresh();
        $stored = $customer->avatar_path;
        Storage::disk('public')->assertExists($stored);

        $response = $this->actingAs($customer, 'web')
            ->delete(route('front.profile.avatar.delete'));

        $response->assertRedirect(route('front.profile.index'));
        $customer->refresh();
        $this->assertNull($customer->avatar_path);
        Storage::disk('public')->assertMissing($stored);
    }

    public function test_initials_helper_handles_missing_user(): void
    {
        $this->assertSame('·', AvatarService::initials(null));
    }

    public function test_admin_can_upload_their_own_avatar(): void
    {
        $admin = Admin::factory()->create();
        $file = UploadedFile::fake()->image('boss.png', 600, 600);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.profile.avatar.upload'), ['avatar' => $file]);

        $response->assertRedirect(route('admin.staffs.profile'));
        $admin->refresh();
        $this->assertNotNull($admin->avatar_path);
        Storage::disk('public')->assertExists($admin->avatar_path);
    }

    public function test_admin_can_remove_their_own_avatar(): void
    {
        $admin = Admin::factory()->create();
        AvatarService::upload($admin, UploadedFile::fake()->image('boss.png', 100, 100));
        $admin->refresh();
        $previous = $admin->avatar_path;
        $this->assertNotNull($previous);

        $response = $this->actingAs($admin, 'admin')
            ->delete(route('admin.profile.avatar.delete'));

        $response->assertRedirect(route('admin.staffs.profile'));
        $admin->refresh();
        $this->assertNull($admin->avatar_path);
        Storage::disk('public')->assertMissing($previous);
    }

    public function test_admin_can_upload_avatar_for_another_staff_member(): void
    {
        $manager = Admin::factory()->create();
        $teamMate = Admin::factory()->create();
        $file = UploadedFile::fake()->image('teammate.png', 256, 256);

        $response = $this->actingAs($manager, 'admin')
            ->post(route('admin.staffs.avatar.upload', ['staff' => $teamMate]), [
                'avatar' => $file,
            ]);

        $response->assertRedirect(route('admin.staffs.show', $teamMate));
        $teamMate->refresh();
        $this->assertNotNull($teamMate->avatar_path);
        Storage::disk('public')->assertExists($teamMate->avatar_path);
    }
}

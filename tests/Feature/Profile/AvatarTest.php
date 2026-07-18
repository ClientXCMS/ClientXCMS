<?php

namespace Tests\Feature\Profile;

use App\Models\Account\Customer;
use App\Services\Account\AvatarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvatarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_customer_can_upload_and_replace_an_avatar(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer)->post(route('front.profile.avatar.upload'), [
            'avatar' => UploadedFile::fake()->image('first.jpg', 600, 400),
        ])->assertRedirect();

        $first = $customer->refresh()->avatar_path;
        Storage::disk('public')->assertExists($first);

        $this->actingAs($customer)->post(route('front.profile.avatar.upload'), [
            'avatar' => UploadedFile::fake()->image('second.png', 256, 256),
        ])->assertRedirect();

        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($customer->refresh()->avatar_path);
    }

    public function test_customer_can_delete_an_avatar(): void
    {
        $customer = Customer::factory()->create();
        $path = app(AvatarService::class)->upload($customer, UploadedFile::fake()->image('avatar.png'));

        $this->actingAs($customer)->delete(route('front.profile.avatar.delete'))->assertRedirect();

        $this->assertNull($customer->refresh()->avatar_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_non_image_avatar_is_rejected(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer)->post(route('front.profile.avatar.upload'), [
            'avatar' => UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('avatar');
    }
}

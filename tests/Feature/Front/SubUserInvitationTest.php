<?php

namespace Tests\Feature\Front;

use App\Models\Account\Customer;
use App\Models\Account\CustomerAccountInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SubUserInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_creates_pending_invitation_row(): void
    {
        Mail::fake();
        $owner = Customer::factory()->create();
        $subUser = Customer::factory()->create();
        $service = $this->createServiceModel($owner->id);

        $this->actingAs($owner)->post(route('front.subusers.store'), [
            'email' => $subUser->email,
            'permissions' => ['service.show', 'invoice.show'],
            'services' => [$service->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('customer_account_invitations', [
            'owner_customer_id' => $owner->id,
            'email' => $subUser->email,
            'all_services' => false,
            'accepted_at' => null,
        ]);
        $this->assertDatabaseMissing('customer_account_accesses', [
            'owner_customer_id' => $owner->id,
            'sub_customer_id' => $subUser->id,
        ]);
    }

    public function test_existing_email_invitation_can_be_accepted_through_plain_token(): void
    {
        $owner = Customer::factory()->create();
        $subUser = Customer::factory()->create(['email_verified_at' => now()]);
        $service = $this->createServiceModel($owner->id);

        // The POST path persists the sha256 of the token and the plain
        // value lives only on the freshly created instance, so we go
        // through the factory directly here. The POST -> persist path
        // is covered by test_post_creates_pending_invitation_row above.
        $invitation = CustomerAccountInvitation::create([
            'owner_customer_id' => $owner->id,
            'email' => $subUser->email,
            'permissions' => ['service.show', 'invoice.show'],
            'all_services' => false,
        ]);
        $invitation->services()->sync([$service->id]);

        $this->actingAs($subUser)
            ->get(route('front.subusers.accept', $invitation->plain_text_token))
            ->assertRedirect(route('front.client.index'));

        $this->assertDatabaseHas('customer_account_accesses', [
            'owner_customer_id' => $owner->id,
            'sub_customer_id' => $subUser->id,
            'all_services' => false,
        ]);
    }

    public function test_unknown_email_invitation_can_be_accepted_after_registration(): void
    {
        $owner = Customer::factory()->create();
        $service = $this->createServiceModel($owner->id);
        $email = 'future@example.com';

        $invitation = CustomerAccountInvitation::create([
            'owner_customer_id' => $owner->id,
            'email' => $email,
            'permissions' => ['service.show'],
            'all_services' => false,
        ]);
        $invitation->services()->sync([$service->id]);

        $newCustomer = Customer::factory()->create(['email' => $email, 'email_verified_at' => now()]);

        $this->actingAs($newCustomer)
            ->get(route('front.subusers.accept', $invitation->plain_text_token))
            ->assertRedirect(route('front.client.index'));

        $this->assertDatabaseHas('customer_account_accesses', [
            'owner_customer_id' => $owner->id,
            'sub_customer_id' => $newCustomer->id,
        ]);
        $this->assertNotNull($invitation->refresh()->accepted_at);
    }

    public function test_revoked_invitation_is_rejected(): void
    {
        $owner = Customer::factory()->create();
        $customer = Customer::factory()->create(['email' => 'revoked@example.com', 'email_verified_at' => now()]);
        $invitation = CustomerAccountInvitation::create([
            'owner_customer_id' => $owner->id,
            'email' => $customer->email,
            'permissions' => ['invoice.show'],
            'all_services' => true,
            'revoked_at' => now(),
        ]);

        $this->actingAs($customer)
            ->get(route('front.subusers.accept', $invitation->plain_text_token))
            ->assertNotFound();
    }
}

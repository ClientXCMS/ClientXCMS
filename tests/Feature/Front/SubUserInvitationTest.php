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

    public function test_existing_email_creates_pending_invitation_until_accepted(): void
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

        $invitation = CustomerAccountInvitation::where('email', $subUser->email)->firstOrFail();

        $this->assertDatabaseMissing('customer_account_accesses', [
            'owner_customer_id' => $owner->id,
            'sub_customer_id' => $subUser->id,
        ]);
        $this->actingAs($subUser)
            ->get(route('front.subusers.accept', $invitation->token))
            ->assertRedirect(route('front.client.index'));
        $this->assertDatabaseHas('customer_account_accesses', [
            'owner_customer_id' => $owner->id,
            'sub_customer_id' => $subUser->id,
            'all_services' => false,
        ]);
    }

    public function test_unknown_email_creates_invitation_and_can_be_accepted(): void
    {
        Mail::fake();
        $owner = Customer::factory()->create();
        $service = $this->createServiceModel($owner->id);
        $email = 'future@example.com';

        $this->actingAs($owner)->post(route('front.subusers.store'), [
            'email' => $email,
            'permissions' => ['service.show'],
            'services' => [$service->id],
        ])->assertRedirect();

        $invitation = CustomerAccountInvitation::where('email', $email)->firstOrFail();
        $newCustomer = Customer::factory()->create(['email' => $email]);

        $this->actingAs($newCustomer)
            ->get(route('front.subusers.accept', $invitation->token))
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
        $customer = Customer::factory()->create(['email' => 'revoked@example.com']);
        $invitation = CustomerAccountInvitation::create([
            'owner_customer_id' => $owner->id,
            'email' => $customer->email,
            'permissions' => ['invoice.show'],
            'all_services' => true,
            'revoked_at' => now(),
        ]);

        $this->actingAs($customer)
            ->get(route('front.subusers.accept', $invitation->token))
            ->assertNotFound();
    }
}

<?php

namespace App\Http\Controllers\Admin\Core;

use App\Http\Controllers\Controller;
use App\Models\Account\Customer;
use App\Models\Account\CustomerAccountAccess;
use App\Models\Account\CustomerAccountInvitation;
use App\Models\ActionLog;
use Illuminate\Http\Request;

class CustomerSubUserController extends Controller
{
    public function update(Request $request, Customer $customer, CustomerAccountAccess $access)
    {
        abort_if(! staff_has_permission('admin.manage_customers'), 403);
        abort_if($access->owner_customer_id !== $customer->id, 404);
        $validated = $this->validatePayload($request, $customer);

        $access->update([
            'permissions' => $validated['permissions'],
            'all_services' => $validated['all_services'],
        ]);
        $this->syncServices($access, $validated);
        $this->log('admin_customer_account_access_updated', $access->id, $customer->id, $access->subCustomer->email);

        return back()->with('success', __('client.subusers.alerts.access_updated'));
    }

    public function destroy(Customer $customer, CustomerAccountAccess $access)
    {
        abort_if(! staff_has_permission('admin.manage_customers'), 403);
        abort_if($access->owner_customer_id !== $customer->id, 404);
        $email = $access->subCustomer->email;
        $access->delete();
        $this->log('admin_customer_account_access_revoked', $access->id, $customer->id, $email);

        return back()->with('success', __('client.subusers.alerts.access_revoked'));
    }

    public function revokeInvitation(Customer $customer, CustomerAccountInvitation $invitation)
    {
        abort_if(! staff_has_permission('admin.manage_customers'), 403);
        abort_if($invitation->owner_customer_id !== $customer->id, 404);
        $invitation->forceFill(['revoked_at' => now()])->save();
        $this->log('admin_customer_account_invitation_revoked', $invitation->id, $customer->id, $invitation->email);

        return back()->with('success', __('client.subusers.alerts.invitation_revoked'));
    }

    private function validatePayload(Request $request, Customer $owner): array
    {
        $validated = $request->validate([
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'in:'.implode(',', CustomerAccountAccess::PERMISSIONS)],
            'all_services' => ['nullable', 'boolean'],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer', 'exists:services,id'],
        ]);
        $validated['permissions'] = array_values(array_unique($validated['permissions']));
        $validated['all_services'] = $request->boolean('all_services');
        $validated['services'] = collect($validated['services'] ?? [])
            ->intersect($owner->services(true)->pluck('id'))
            ->values()
            ->all();

        return $validated;
    }

    private function syncServices(CustomerAccountAccess $access, array $validated): void
    {
        if ($validated['all_services']) {
            $access->services()->detach();

            return;
        }

        $access->services()->sync($validated['services']);
    }

    private function log(string $message, int $modelId, int $customerId, string $email): void
    {
        ActionLog::log(ActionLog::OTHER, CustomerAccountAccess::class, $modelId, auth('admin')->id(), $customerId, [
            'message' => $message,
            'email' => $email,
        ]);
    }
}

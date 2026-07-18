<?php

namespace App\Http\Controllers\Admin\Core;

use App\Http\Controllers\Admin\AbstractCrudController;
use App\Models\Account\Customer;
use App\Models\Account\CustomerAccountAccess;
use App\Models\Account\CustomerAccountInvitation;
use App\Models\ActionLog;
use Illuminate\Http\Request;

class CustomerSubUserController extends AbstractCrudController
{
    protected string $viewPath = 'admin.core.customers';

    protected string $routePath = 'admin.customers';

    protected string $model = CustomerAccountAccess::class;

    public function update(Request $request, Customer $customer, CustomerAccountAccess $access)
    {
        $this->checkPermission('update', $access);
        $this->ensureBelongsToCustomer($customer, $access->owner_customer_id);
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
        $this->checkPermission('delete', $access);
        $this->ensureBelongsToCustomer($customer, $access->owner_customer_id);
        $email = $access->subCustomer->email;
        $access->delete();
        $this->log('admin_customer_account_access_revoked', $access->id, $customer->id, $email);

        return back()->with('success', __('client.subusers.alerts.access_revoked'));
    }

    public function revokeInvitation(Customer $customer, CustomerAccountInvitation $invitation)
    {
        $this->checkPermission('delete');
        $this->ensureBelongsToCustomer($customer, $invitation->owner_customer_id);
        $invitation->forceFill(['revoked_at' => now()])->save();
        $this->log('admin_customer_account_invitation_revoked', $invitation->id, $customer->id, $invitation->email);

        return back()->with('success', __('client.subusers.alerts.invitation_revoked'));
    }

    protected function getPermissions(string $tablename): array
    {
        return [
            'showAny' => 'admin.show_customers',
            'show' => 'admin.show_customers',
            'create' => 'admin.manage_customers',
            'update' => 'admin.manage_customers',
            'delete' => 'admin.manage_customers',
        ];
    }

    private function ensureBelongsToCustomer(Customer $customer, int $ownerCustomerId): void
    {
        abort_if($ownerCustomerId !== $customer->id, 404);
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

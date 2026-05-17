<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Mail\Account\CustomerAccountAccessGrantedEmail;
use App\Mail\Account\CustomerAccountInvitationEmail;
use App\Mail\Account\EmailAddressNotifiable;
use App\Models\Account\Customer;
use App\Models\Account\CustomerAccountAccess;
use App\Models\Account\CustomerAccountInvitation;
use App\Models\ActionLog;
use App\Models\Provisioning\Service;
use Illuminate\Http\Request;

class SubUserController extends Controller
{
    public function index()
    {
        $customer = auth()->user();
        $accesses = $customer->ownedAccountAccesses()->with(['subCustomer', 'services'])->orderBy('created_at', 'desc')->get();
        $invitations = $customer->pendingAccountInvitations()->with('services')->orderBy('created_at', 'desc')->get();
        $receivedAccesses = $customer->receivedAccountAccesses()->with(['owner', 'services'])->orderBy('created_at', 'desc')->get();
        $services = $customer->services()->where('status', 'active')->orderBy('name')->get();
        $permissions = $this->permissions();

        return view('front.subusers.index', compact('accesses', 'invitations', 'receivedAccesses', 'services', 'permissions'));
    }

    public function store(Request $request)
    {
        $owner = auth()->user();
        $validated = $this->validatePayload($request, $owner);
        $email = strtolower($validated['email']);

        if ($email === strtolower($owner->email)) {
            return back()->with('error', __('client.subusers.alerts.self_invite'));
        }
        if ($owner->pendingAccountInvitations()->where('email', $email)->exists()) {
            return back()->with('error', __('client.subusers.alerts.pending_exists'));
        }

        $existingCustomer = Customer::where('email', $email)->first();
        if ($existingCustomer && $owner->ownedAccountAccesses()->where('sub_customer_id', $existingCustomer->id)->exists()) {
            return back()->with('error', __('client.subusers.alerts.access_exists'));
        }

        $invitation = CustomerAccountInvitation::create([
            'owner_customer_id' => $owner->id,
            'email' => $email,
            'permissions' => $validated['permissions'],
            'all_services' => $validated['all_services'],
        ]);
        $this->syncServices($invitation, $validated);
        $this->sendInvitation($invitation);
        $this->log('customer_account_invitation_created', $invitation->id, $owner->id, $email);

        return back()->with('success', __('client.subusers.alerts.invitation_sent'));
    }

    public function service(Service $service)
    {
        abort_if($service->customer_id !== auth()->id(), 404);

        $accesses = auth()->user()->ownedAccountAccesses()
            ->with(['subCustomer', 'services'])
            ->orderBy('created_at', 'desc')
            ->get();
        $invitations = auth()->user()->pendingAccountInvitations()
            ->with('services')
            ->where(function ($query) use ($service) {
                $query->where('all_services', true)
                    ->orWhereHas('services', function ($services) use ($service) {
                        $services->whereKey($service->id);
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get();
        $servicePermissions = CustomerAccountAccess::SERVICE_PERMISSIONS;
        $invoicePermissions = CustomerAccountAccess::INVOICE_PERMISSIONS;



        return view('front.provisioning.services.subusers', compact('service', 'accesses', 'invitations', 'servicePermissions', 'invoicePermissions'));
    }

    public function storeService(Request $request, Service $service)
    {
        abort_if($service->customer_id !== auth()->id(), 404);

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'in:'.implode(',', CustomerAccountAccess::PERMISSIONS)],
        ]);
        if (! collect($validated['permissions'])->contains(fn ($permission) => str_starts_with($permission, 'service.'))) {
            return back()->with('error', __('client.subusers.alerts.service_permission_required'));
        }
        $owner = auth()->user();
        $email = strtolower($validated['email']);

        if ($email === strtolower($owner->email)) {
            return back()->with('error', __('client.subusers.alerts.self_invite'));
        }
        if ($owner->pendingAccountInvitations()->where('email', $email)->whereHas('services', fn ($services) => $services->whereKey($service->id))->exists()) {
            return back()->with('error', __('client.subusers.alerts.pending_exists'));
        }
        $existingCustomer = Customer::where('email', $email)->first();
        if ($existingCustomer && $owner->ownedAccountAccesses()->where('sub_customer_id', $existingCustomer->id)->where(function ($query) use ($service) {
            $query->where('all_services', true)->orWhereHas('services', fn ($services) => $services->whereKey($service->id));
        })->exists()) {
            return back()->with('error', __('client.subusers.alerts.access_exists'));
        }

        $invitation = CustomerAccountInvitation::create([
            'owner_customer_id' => $owner->id,
            'email' => $email,
            'permissions' => $this->applyPermissionDependencies($validated['permissions']),
            'all_services' => false,
        ]);
        $invitation->services()->sync([$service->id]);
        $this->sendInvitation($invitation);
        $this->log('customer_account_service_invitation_created', $invitation->id, $owner->id, $email);

        return back()->with('success', __('client.subusers.alerts.invitation_sent'));
    }

    public function update(Request $request, CustomerAccountAccess $access)
    {
        abort_if($access->owner_customer_id !== auth()->id(), 404);
        $validated = $this->validatePayload($request, auth()->user(), false);
        $access->update([
            'permissions' => $validated['permissions'],
            'all_services' => $validated['all_services'],
        ]);
        $this->syncServices($access, $validated);
        $this->log('customer_account_access_updated', $access->id, auth()->id(), $access->subCustomer->email);

        return back()->with('success', __('client.subusers.alerts.access_updated'));
    }

    public function destroy(CustomerAccountAccess $access)
    {
        abort_if($access->owner_customer_id !== auth()->id() && $access->sub_customer_id !== auth()->id(), 404);
        $email = $access->subCustomer->email;
        $access->delete();
        $this->log('customer_account_access_revoked', $access->id, auth()->id(), $email);

        return back()->with('success', __('client.subusers.alerts.access_revoked'));
    }

    public function resend(CustomerAccountInvitation $invitation)
    {
        abort_if($invitation->owner_customer_id !== auth()->id(), 404);
        abort_if(! $invitation->isPending(), 404);
        $invitation->forceFill(['expires_at' => now()->addDays(14)])->save();
        $this->sendInvitation($invitation);

        return back()->with('success', __('client.subusers.alerts.invitation_resent'));
    }

    public function revoke(CustomerAccountInvitation $invitation)
    {
        abort_if($invitation->owner_customer_id !== auth()->id(), 404);
        $invitation->forceFill(['revoked_at' => now()])->save();
        $this->log('customer_account_invitation_revoked', $invitation->id, auth()->id(), $invitation->email);

        return back()->with('success', __('client.subusers.alerts.invitation_revoked'));
    }

    public function accept(Request $request, string $token)
    {
        $invitation = CustomerAccountInvitation::where('token', $token)->firstOrFail();
        if (! $invitation->isPending()) {
            if (auth()->check() && strtolower(auth()->user()->email) === strtolower($invitation->email) && $invitation->accepted_at !== null) {
                return redirect()->route('front.client.index')->with('success', __('client.subusers.alerts.invitation_accepted'));
            }
            abort(404);
        }

        if (! auth()->check()) {
            $request->session()->put('customer_account_invitation_token', $token);
            $route = Customer::where('email', $invitation->email)->exists() ? 'login' : 'register';

            return redirect()->route($route, [
                'redirect' => route('front.subusers.accept', $token),
                'email' => $invitation->email,
            ]);
        }

        $access = $invitation->accept(auth()->user());
        $this->sendAccessGranted($access);
        $request->session()->forget('customer_account_invitation_token');

        return redirect()->route('front.client.index')->with('success', __('client.subusers.alerts.invitation_accepted'));
    }

    public function updateService(Request $request, Service $service)
    {
        abort_if($service->customer_id !== auth()->id(), 404);

        $validated = $request->validate([
            'access' => ['nullable', 'array'],
            'access.*' => ['integer', 'exists:customer_account_accesses,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['array'],
            'permissions.*.*' => ['in:'.implode(',', CustomerAccountAccess::SERVICE_PERMISSIONS)],
        ]);

        $selectedAccessIds = collect($validated['access'] ?? [])->map(fn ($id) => (int) $id);
        $accesses = auth()->user()->ownedAccountAccesses()->get();

        foreach ($accesses as $access) {
            if (! $access->all_services) {
                $hasAccess = $selectedAccessIds->contains($access->id);
                $services = $access->services()->pluck('services.id');
                if ($hasAccess) {
                    $services->push($service->id);
                } else {
                    $services = $services->reject(fn ($id) => (int) $id === (int) $service->id);
                }
                $access->services()->sync($services->unique()->values()->all());
            }

            $permissions = collect($access->permissions ?? [])->reject(fn ($permission) => str_starts_with($permission, 'service.'));
            $servicePermissions = collect($validated['permissions'][$access->id] ?? []);
            $access->update(['permissions' => $this->applyPermissionDependencies($permissions->merge($servicePermissions)->all())]);
        }

        return back()->with('success', __('client.subusers.alerts.service_access_updated'));
    }

    private function validatePayload(Request $request, Customer $owner, bool $requireEmail = true): array
    {
        $rules = [
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'in:'.implode(',', CustomerAccountAccess::PERMISSIONS)],
            'all_services' => ['nullable', 'boolean'],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer', 'exists:services,id'],
        ];
        if ($requireEmail) {
            $rules['email'] = ['required', 'email'];
        }

        $validated = $request->validate($rules);
        $validated['permissions'] = $this->applyPermissionDependencies($validated['permissions']);
        $validated['all_services'] = $request->boolean('all_services');
        $validated['services'] = collect($validated['services'] ?? [])
            ->intersect($owner->services(true)->pluck('id'))
            ->values()
            ->all();

        if (! $validated['all_services'] && empty($validated['services']) && collect($validated['permissions'])->contains(fn ($permission) => str_starts_with($permission, 'service.'))) {
            abort(422, __('client.subusers.alerts.service_required'));
        }

        return $validated;
    }

    private function syncServices(CustomerAccountAccess|CustomerAccountInvitation $model, array $validated): void
    {
        if ($validated['all_services']) {
            $model->services()->detach();

            return;
        }

        $model->services()->sync($validated['services']);
    }

    private function sendInvitation(CustomerAccountInvitation $invitation): void
    {
        try {
            $recipient = Customer::where('email', $invitation->email)->first()
                ?? new EmailAddressNotifiable($invitation->email, locale: $invitation->owner->locale);
            $recipient->notify(new CustomerAccountInvitationEmail($invitation));
        } catch (\Exception $e) {
            \Cache::put('notification_error', $e->getMessage().' | Date : '.date('Y-m-d H:i:s'), 3600 * 24);
        }
    }

    private function sendAccessGranted(CustomerAccountAccess $access): void
    {
        try {
            $access->subCustomer->notify(new CustomerAccountAccessGrantedEmail($access));
        } catch (\Exception $e) {
            \Cache::put('notification_error', $e->getMessage().' | Date : '.date('Y-m-d H:i:s'), 3600 * 24);
        }
    }

    private function permissions(): array
    {
        return [
            'services' => CustomerAccountAccess::SERVICE_PERMISSIONS,
            'invoices' => CustomerAccountAccess::INVOICE_PERMISSIONS,
        ];
    }

    private function applyPermissionDependencies(array $permissions): array
    {
        $permissions = collect($permissions)->unique()->values();

        if ($permissions->intersect(CustomerAccountAccess::SERVICE_PERMISSIONS_REQUIRING_INVOICES)->isNotEmpty()) {
            $permissions = $permissions->merge(CustomerAccountAccess::INVOICE_PERMISSIONS);
        }

        return $permissions->unique()->values()->all();
    }

    private function log(string $message, int $modelId, int $customerId, string $email): void
    {
        ActionLog::log(ActionLog::OTHER, CustomerAccountAccess::class, $modelId, null, $customerId, [
            'message' => $message,
            'email' => $email,
        ]);
    }
}

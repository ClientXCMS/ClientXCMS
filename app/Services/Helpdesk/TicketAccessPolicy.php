<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Services\Helpdesk;

use App\Models\Account\Customer;
use App\Models\Billing\Invoice;
use App\Models\Helpdesk\SupportAccessRule;
use App\Models\Helpdesk\SupportDepartment;
use App\Models\Provisioning\Service;
use Illuminate\Support\Collection;

/**
 * v2.16 — Evaluates the configurable {@see SupportAccessRule}s against
 * a draft ticket and returns a list of violations (empty array == OK).
 *
 * Designed to be the single source of truth used by:
 *   - SubmitTicketRequest::rules() (server-side enforcement)
 *   - SupportController::create()   (filters departments / priorities
 *                                    the user is allowed to choose in
 *                                    the form)
 *   - Admin tooling (future CRUD)
 */
class TicketAccessPolicy
{
    /**
     * Returns the array of violation messages (translated already) the
     * draft ticket triggers. Empty array means "OK to submit".
     *
     * @param  Customer  $customer       the would-be ticket author
     * @param  int|null  $departmentId   the chosen department
     * @param  string    $priority       low|medium|high
     * @param  string|null $relatedType  service|invoice|null
     * @param  int|null  $relatedId      pk of the related entity
     */
    public function violations(
        Customer $customer,
        ?int $departmentId,
        string $priority,
        ?string $relatedType = null,
        ?int $relatedId = null
    ): array {
        $violations = [];
        $service = $this->resolveLinkedService($relatedType, $relatedId);
        $relatedProductId = $service?->product_id;

        $rules = SupportAccessRule::query()
            ->enabled()
            ->orderBy('priority')
            ->get()
            ->filter(fn (SupportAccessRule $r) => $this->ruleAppliesToScope($r, $departmentId, $relatedProductId));

        foreach ($rules as $rule) {
            $error = $this->evaluate($rule, $customer, $priority, $service, $relatedType);
            if ($error !== null) {
                $violations[] = $error;
            }
        }

        return $violations;
    }

    /**
     * Filter the array of departments the customer is allowed to pick
     * in the create form. A department is hidden when at least one
     * enabled rule scoped to that department blocks the chosen
     * (lowest) priority `low` with no other configurable bypass.
     *
     * The helper is conservative: when in doubt the department stays
     * visible — the server-side `violations()` is the actual gate.
     */
    public function filterDepartments(Collection $departments, Customer $customer): Collection
    {
        return $departments->filter(function (SupportDepartment $department) use ($customer) {
            $violations = $this->violations($customer, $department->id, 'low');
            return empty($violations);
        });
    }

    /**
     * Return the priorities the customer can still pick given their
     * services + already-chosen department.
     *
     * @return string[]
     */
    public function allowedPriorities(Customer $customer, ?int $departmentId): array
    {
        $all = ['low', 'medium', 'high'];
        return array_values(array_filter($all, function (string $priority) use ($customer, $departmentId) {
            $violations = $this->violations($customer, $departmentId, $priority);
            // The rule that blocked a priority would have surfaced a
            // priority-specific message — strip those and keep the
            // priority only if no message remained.
            return empty($violations);
        }));
    }

    private function ruleAppliesToScope(SupportAccessRule $rule, ?int $departmentId, ?int $productId): bool
    {
        if ($rule->scope_department_id !== null && $rule->scope_department_id !== $departmentId) {
            return false;
        }
        if ($rule->scope_product_id !== null && $rule->scope_product_id !== $productId) {
            return false;
        }
        return true;
    }

    /**
     * Returns the translated violation message or null when the rule
     * accepts the draft.
     */
    private function evaluate(
        SupportAccessRule $rule,
        Customer $customer,
        string $priority,
        ?Service $service,
        ?string $relatedType
    ): ?string {
        // 1. Priority block — checked first because it's the most
        //    common use-case (limit `high` to certain customers).
        if (in_array($priority, $rule->blocked_priorities, true)) {
            return $this->translate($rule);
        }

        // 2. Active-service requirement (any product, any status=active)
        if ($rule->requires_active_service) {
            $hasActive = $customer->services()
                ->whereIn('status', [Service::STATUS_ACTIVE])
                ->exists();
            if (! $hasActive) {
                return $this->translate($rule);
            }
        }

        // 3. The ticket must be linked to a specific related type.
        if ($rule->required_related_type !== null && $rule->required_related_type !== $relatedType) {
            return $this->translate($rule);
        }

        // 4. Status of the linked service must match a whitelist.
        $requiredStatuses = $rule->required_service_statuses;
        if (! empty($requiredStatuses)) {
            if ($service === null) {
                return $this->translate($rule);
            }
            if (! in_array($service->status, $requiredStatuses, true)) {
                return $this->translate($rule);
            }
        }

        // 5. The linked service must own a specific config option.
        if ($rule->required_option_uuid !== null) {
            if ($service === null) {
                return $this->translate($rule);
            }
            $owns = $service->configoptions()
                ->whereHas('option', fn ($q) => $q->where('uuid', $rule->required_option_uuid))
                ->exists();
            if (! $owns) {
                return $this->translate($rule);
            }
        }

        return null;
    }

    private function resolveLinkedService(?string $relatedType, ?int $relatedId): ?Service
    {
        if ($relatedType !== 'service' || $relatedId === null) {
            return null;
        }
        return Service::find($relatedId);
    }

    private function translate(SupportAccessRule $rule): string
    {
        $key = $rule->message_key ?: 'v216::helpdesk.access.default_denied';
        $translated = __($key, ['rule' => $rule->name]);
        // If the key itself is returned (i.e. not translated), fall back.
        if ($translated === $key) {
            $translated = __('v216::helpdesk.access.default_denied', ['rule' => $rule->name]);
            if ($translated === 'v216::helpdesk.access.default_denied') {
                $translated = sprintf('Access denied by rule "%s".', $rule->name);
            }
        }
        return $translated;
    }
}

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
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2025
 */

namespace App\Http\Controllers\Admin\Provisioning;

use App\DTO\Store\ConfigOptionDTO;
use App\Http\Controllers\Admin\AbstractCrudController;
use App\Http\Requests\Provisioning\UpdateConfigOptionServiceRequest;
use App\Models\Billing\ConfigOption;
use App\Models\Provisioning\ConfigOptionService;
use App\Models\Provisioning\Service;
use App\Models\Store\Pricing;
use App\Services\Store\PricingService;
use App\Services\Store\RecurringService;
use Illuminate\Http\Request;

class ConfigOptionServiceController extends AbstractCrudController
{
    protected string $model = ConfigOptionService::class;

    protected string $viewPath = 'admin.provisioning.config-options-services';

    protected string $translatePrefix = 'provisioning.admin.configoptions_services';

    protected string $routePath = 'admin.configoptions_services';

    protected array $filters = ['config_option_id', 'service_id'];

    protected array $sorts = ['id', 'config_option_id', 'service_id', 'value'];

    protected array $relations = ['option', 'service'];

    protected function getSearchFields()
    {
        return [
            'service.customer.email' => __('global.customer'),
            'id' => 'Identifier',
            'service.id' => __('provisioning.service'),
            'config_option_id' => __('provisioning.config_option'),
        ];
    }

    public function show(ConfigOptionService $configoptions_service)
    {
        $options = ConfigOption::all()->mapWithKeys(function ($item) {
            return [$item->id => $item->name];
        });
        $data['item'] = $configoptions_service;
        $data['options'] = $options;
        $data['pricing'] = $configoptions_service->getPricing();
        $data['recurrings'] = app(RecurringService::class)->getRecurrings();

        return $this->showView($data);
    }

    public function update(UpdateConfigOptionServiceRequest $request, ConfigOptionService $configoptions_service)
    {
        $validated = $request->validated();
        $configoptions_service->update($validated);
        Pricing::createOrUpdateFromArray($validated, $configoptions_service->id, 'config_options_service');
        PricingService::forgot();

        return $this->updateRedirect($configoptions_service);
    }

    public function create(Request $request)
    {
        $serviceId = $request->get('service_id');

        return $this->createView(['options' => ConfigOption::all(), 'service_id' => $serviceId]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'value' => 'required',
            'config_option_id' => 'required|exists:config_options,id',
            'service_id' => 'required|exists:services,id',
            'sync_with_service' => 'nullable|boolean',
        ]);
        $configoption = ConfigOption::find($validated['config_option_id']);
        /** @var Service $service */
        $service = Service::find($validated['service_id']);
        $expires_at = app(RecurringService::class)->addFromNow($service->billing);
        $dto = new ConfigOptionDTO($configoption, $validated['value'], null);
        $validated = $request->validate([
            'value' => $dto->validate(),
        ]);
        $service->saveOptions([$configoption->key => $validated['value']]);

        return back()->with('success', __($this->flashs['created']));
    }

    public function destroy(ConfigOptionService $configoptions_service)
    {
        $configoptions_service->delete();
        Pricing::where('related_id', $configoptions_service->id)
            ->where('related_type', 'config_options_service')
            ->delete();
        PricingService::forgot();

        return $this->destroyRedirect($configoptions_service);
    }
}

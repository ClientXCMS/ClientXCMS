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

use App\Http\Controllers\Admin\AbstractCrudController;
use App\Http\Requests\Provisioning\UpdateConfigOptionOptionRequest;
use App\Http\Requests\Store\ConfigOptionRequest;
use App\Models\Billing\ConfigOption;
use App\Models\Billing\ConfigOptionsOption;
use App\Models\Provisioning\ConfigOptionService;
use App\Models\Store\Pricing;
use App\Models\Store\Product;
use App\Services\Store\PricingService;
use App\Services\Store\RecurringService;
use Illuminate\Http\Request;

class ConfigOptionController extends AbstractCrudController
{
    protected string $model = ConfigOption::class;

    protected string $routePath = 'admin.configoptions';

    protected string $viewPath = 'admin.provisioning.config-options';

    protected string $translatePrefix = 'provisioning.admin.configoptions';

    protected ?string $managedPermission = 'admin.manage_configoptions';

    public function getCreateParams()
    {
        $params = parent::getCreateParams();
        $params['types'] = ConfigOption::getTypes();
        $params['keys'] = ConfigOption::getKeys();
        $params['products'] = Product::pluck('name', 'id');
        $params['pricing'] = new Pricing;
        $params['recurrings'] = (new RecurringService)->getRecurrings();

        return $params;
    }

    public function getIndexParams($items, string $translatePrefix)
    {
        $params = parent::getIndexParams($items, $translatePrefix);
        $params['keys'] = ConfigOption::getKeysForItems($items);

        return $params;
    }

    public function store(ConfigOptionRequest $request)
    {

        $this->checkPermission('create');
        $validated = $request->only(['type', 'key', 'name', 'rules', 'min_value', 'max_value', 'unit', 'step', 'default_value', 'required']);
        if ($request->key == 'custom') {
            $validated['key'] = $request->input('custom_key');
        }
        $validated['hidden'] = $request->has('hidden');
        $validated['required'] = $request->has('required');
        $validated['sort_order'] = ConfigOption::where('type', $validated['type'])->max('sort_order') + 1;
        /** @var ConfigOption $option */
        $option = ConfigOption::create($validated);
        $option->products()->sync($request->input('products'));
        if (in_array($option->type, ['dropdown', 'radio'])) {
            $option->fill(['hidden' => 1])->save();
            $option->addOption('Option 1', 'option_1');
        }
        if ($request->has('pricing')) {
            Pricing::createFromArray($request->only('pricing'), $option->id, 'config_option');
            PricingService::forgot();
        }

        return $this->storeRedirect($option);
    }

    public function show(ConfigOption $configoption)
    {
        $this->checkPermission('show');
        $params['item'] = $configoption;
        $params['types'] = ConfigOption::getTypes();
        $params['keys'] = ConfigOption::getKeys();
        $params['products'] = Product::pluck('name', 'id');
        $params['selectedProducts'] = $configoption->products->pluck('id')->toArray();
        $params['pricing'] = Pricing::where('related_id', $configoption->id)->where('related_type', 'config_option')->first() ?? new Pricing;
        $params['recurrings'] = (new RecurringService)->getRecurrings();
        $params['optionsPricing'] = collect($configoption->options)->mapWithKeys(function ($option) {
            return [$option->id => Pricing::where('related_id', $option->id)->where('related_type', 'config_options_option')->first() ?? new Pricing];
        });
        if ($configoption->hidden) {
            session()->flash('warning', __('provisioning.admin.configoptions.hidden'));
        }

        return $this->showView($params);
    }

    public function update(ConfigOptionRequest $request, ConfigOption $configoption)
    {
        $this->checkPermission('update');
        $data = $request->only(['type', 'key', 'name', 'rules', 'min_value', 'max_value', 'unit', 'step', 'default_value', 'required', 'hidden', 'sort_order']);
        $data['hidden'] = $request->has('hidden');
        $data['required'] = $request->has('required');
        if ($request->key == 'custom') {
            $data['key'] = $request->input('custom_key');
        }
        $configoption->update($data);
        $configoption->products()->sync($request->input('products'));
        if ($request->has('pricing')) {
            $pricing = Pricing::where('related_id', $configoption->id)->where('related_type', 'config_option')->first();
            $pricing = $pricing ?? new Pricing;
            $pricing->related_id = $configoption->id;
            $pricing->related_type = 'config_option';
            $pricing->updateFromArray($request->all(), 'config_option');
            PricingService::forgot();
        }

        return $this->updateRedirect($configoption);
    }

    public function updateOptions(UpdateConfigOptionOptionRequest $request, ConfigOption $configOption)
    {
        $this->checkPermission('update');
        $validated = $request->validated();
        $rules = [
            'options' => 'required|array',
            'options.*.friendly_name' => 'required|string|max:255',
            'options.*.value' => 'required|string',
            'options.*.hidden' => 'nullable|boolean',
        ];
        $data = $request->all();
        foreach ($request->input('options') as $id => $option) {
            $pricing = $request->input('options.' . $id . '.pricing', []);
            $convertedPricing = [];
            foreach ($pricing as $key => $value) {
                $convertedPricing[$key]['price'] = isset($value['price']) ? str_replace(',', '.', $value['price']) : null;
                $convertedPricing[$key]['setup'] = isset($value['setup']) ? str_replace(',', '.', $value['setup']) : null;
            }
            $rules['options.'.$id.'.pricing.*.price'] = 'nullable|numeric|min:0';
            $rules['options.'.$id.'.pricing.*.setup'] = 'nullable|numeric|max:255';
            $data['options'][$id]['pricing'] = $convertedPricing;
        }

        $validated = \Validator::validate($data, $rules);
        $sortOrder = 1;
        foreach ($validated['options'] as $id => $option) {
            $configOption->options()->updateOrCreate(['id' => $id], [
                'sort_order' => $sortOrder,
                'friendly_name' => $option['friendly_name'],
                'value' => $option['value'],
                'hidden' => $option['hidden'] ?? false,
            ]);
            $sortOrder++;
            Pricing::createOrUpdateFromArray($option, $id, 'config_options_option');
            PricingService::forgot();
        }
        return redirect()->route($this->routePath.'.show', $configOption->id)->with('success', __($this->flashs['updated']));
    }

    public function storeOptions(Request $request, ConfigOption $configOption)
    {
        $this->checkPermission('update');
        $configOption->addOption('Option '.($configOption->options->count() + 1), 'option_'.($configOption->options->count() + 1));

        return redirect()->route($this->routePath.'.show', $configOption->id)->with('success', __($this->translatePrefix.'.option_added'));
    }

    public function destroyOption(ConfigOption $configOption, ConfigOptionsOption $option)
    {
        $this->checkPermission('update');
        if ($configOption->options->count() == 1) {
            return redirect()->route($this->routePath.'.show', $configOption->id)->with('error', __($this->translatePrefix.'.option_last'));
        }
        $option->delete();

        return redirect()->route($this->routePath.'.show', $configOption)->with('success', __($this->flashs['deleted']));
    }

    public function destroy(ConfigOption $configoption)
    {
        $this->checkPermission('delete');
        if (ConfigOptionService::where('config_option_id', $configoption->id)->count() > 0) {
            return redirect()->route($this->routePath.'.index')->with('error', __($this->translatePrefix.'.cannot_delete_in_use'));
        }
        $configoption->delete();

        return redirect()->route($this->routePath.'.index')->with('success', __($this->flashs['deleted']));
    }
}

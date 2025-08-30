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


namespace App\Http\Controllers\Admin\Settings;

use App\Helpers\Countries;
use App\Helpers\EnvEditor;
use App\Http\Controllers\Controller;
use App\Models\Admin\Setting;
use App\Models\Billing\Invoice;
use App\Services\Billing\InvoiceService;
use App\Services\Store\CurrencyService;
use App\Services\Store\TaxesService;
use Illuminate\Http\Request;

class SettingsBillingController extends Controller
{
    public function showBilling()
    {
        $currencies = (new CurrencyService)->getCurrencies()->mapWithKeys(function ($item, $key) {
            return [$key => $item['label']];
        });
        $billing_modes = [
            InvoiceService::INVOICE => __('billing.admin.settings.fields.billing_modes.invoice'),
            InvoiceService::PRO_FORMA => __('billing.admin.settings.fields.billing_modes.proforma'),
        ];
        $options = [TaxesService::PRICE_TTC => __('billing.admin.settings.fields.display_product_price.included'), TaxesService::PRICE_HT => __('billing.admin.settings.fields.display_product_price.excluded')];
        $options2 = [TaxesService::PRICE_TTC => __('billing.admin.settings.fields.store_mode_tax.included'), TaxesService::PRICE_HT => __('billing.admin.settings.fields.store_mode_tax.excluded')];
        $keys = [
            'checkout_toslink' => 'input',
            'app_address' => 'textarea',
            'invoice_terms' => 'textarea',
        ];
        $tmpcountries = Countries::names();
        $countries = collect(TaxesService::arrayVatPercents())->mapWithKeys(function ($item, $key) use ($tmpcountries) {
            return [$key => $tmpcountries[$key].' ('.$item.'%)'];
        });
        $rates = [
            TaxesService::VAT_RATE_BY_COUNTRY => __('billing.admin.settings.fields.rates.vat_rate_by_country'),
            TaxesService::VAT_RATE_FIXED => __('billing.admin.settings.fields.rates.vat_rate_fixed'),
        ];

        return view('admin/settings/billing/billing', compact('countries', 'billing_modes', 'options', 'currencies', 'keys', 'rates', 'options2'));
    }

    public function saveBilling(Request $request)
    {
        $validated = $this->validate($request, [
            'store_mode_tax' => 'in:tax_included,tax_excluded',
            'checkout_customermustbeconfirmed' => 'in:true,false',
            'checkout_toslink' => 'nullable|string|url',
            'store_checkout_webhook_url' => 'nullable|string|url',
            'store_vat_enabled' => 'in:true,false',
            'store_currency' => ['required'],
            'invoice_terms' => 'string|max:1000',
            'app_address' => ['required', 'string', 'max:1000', new \App\Rules\NoScriptOrPhpTags()],
            'billing_invoice_prefix' => 'required|string|max:10',
            'billing_mode' => 'required|in:invoice,proforma',
            'remove_pending_invoice' => 'required|integer|min:0',
            'remove_pending_invoice_type' => 'required|in:cancel,delete',
            'store_vat_rate' => 'required',
            'display_product_price' => 'required|in:tax_included,tax_excluded',
            'add_setupfee_on_upgrade' => 'in:true,false',
            'minimum_days_to_force_renewal_with_upgrade' => 'required|integer|min:0',
            'vat_default_country' => 'required_if:store_vat_rate,'.TaxesService::VAT_RATE_BY_COUNTRY.'|nullable',
            'allow_add_balance_to_invoices' => 'in:true,false',
        ]);
        $validated['store_vat_enabled'] = $validated['store_vat_enabled'] ?? 'false';
        $validated['allow_add_balance_to_invoices'] = $validated['allow_add_balance_to_invoices'] ?? 'false';
        $validated['checkout_customermustbeconfirmed'] = $validated['checkout_customermustbeconfirmed'] ?? 'false';
        $validated['add_setupfee_on_upgrade'] = $validated['add_setupfee_on_upgrade'] ?? 'false';
        if (\setting('billing_invoice_prefix') !== $validated['billing_invoice_prefix']) {
            Invoice::updateInvoicePrefix($validated['billing_invoice_prefix']);
        }
        if (isset($validated['store_vat_rate']) && $validated['store_vat_rate'] == TaxesService::VAT_RATE_FIXED) {
            EnvEditor::updateEnv([
                'STORE_FIXED_VAT_RATE' => TaxesService::arrayVatPercents()[$validated['vat_default_country']],
                'STORE_VAT_COUNTRY' => $validated['vat_default_country'],
            ]);
        } else {
            EnvEditor::updateEnv([
                'STORE_FIXED_VAT_RATE' => null,
                'STORE_VAT_COUNTRY' => null,
            ]);
        }
        Setting::updateSettings($validated);

        return redirect()->back()->with('success', __('billing.admin.settings.success'));

    }
}

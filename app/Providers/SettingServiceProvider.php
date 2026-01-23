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

namespace App\Providers;

use App\Core\Menu\AdminMenuItem;
use App\Http\Controllers\Admin\Core\AdminLocalesController;
use App\Http\Controllers\Admin\Security\ActionsLogController;
use App\Http\Controllers\Admin\Security\ApiKeysController;
use App\Http\Controllers\Admin\Security\DatabaseController;
use App\Http\Controllers\Admin\Security\HistoryController;
use App\Http\Controllers\Admin\Security\LicenseController;
use App\Http\Controllers\Admin\Security\UpdateController;
use App\Http\Controllers\Admin\Settings\SettingsCoreController;
use App\Http\Controllers\Admin\Settings\SettingsExtensionController;
use App\Http\Controllers\Admin\Settings\SettingsSecurityController;
use App\Models\Admin\Permission;
use App\Models\Admin\Setting;
use App\Services\Billing\InvoiceService;
use App\Services\SettingsService;
use App\Services\Store\TaxesService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class SettingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('settings', SettingsService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {

        try {
            /** @var SettingsService $service */
            $service = $this->app->make('settings');
            $service->set($this->loadSettings());
            $service->setSavedSettings($this->loadSettings());
            $service->initTranslatedSettings();
            // Ajout de valeur par default
            $service->setDefaultValue('app_name', config('app.name'));
            $service->setDefaultValue('app_url', request()->getSchemeAndHttpHost());
            $service->setDefaultValue('app_timezone', 'Europe/Paris');
            $service->setDefaultValue('app_address', config('app.name') . ', You can set your address in the settings');
            $service->setDefaultValue('app_debug', config('app.debug', 'false'));
            $service->setDefaultValue('app_env', config('app.env', 'production'));
            $service->setDefaultValue('app_license_refresh_token', setting('app.license.refresh_token'));
            $service->setDefaultValue('store_mode_tax', TaxesService::MODE_TAX_EXCLUDED);
            $service->setDefaultValue('store_vat_enabled', true);
            $service->setDefaultValue('store_enabled', true);
            $service->setDefaultValue('store_redirect_url', null);
            $service->setDefaultValue('billing_invoice_prefix', 'CTX');
            $service->setDefaultValue('days_before_creation_renewal_invoice', 7);
            $service->setDefaultValue('days_before_expiration', 7);
            $service->setDefaultValue('notifications_expiration_days', '7,5,3,1');
            $service->setDefaultValue('days_before_subscription_renewal', 7);
            $service->setDefaultValue('allow_add_balance_to_invoices', true);
            $service->setDefaultValue('max_subscription_tries', 7);
            $service->setDefaultValue('app_default_locale', 'fr_FR');
            $service->setDefaultValue('app_telemetry', env('TELEMETRY_ENABLED', 'true') == 'true');
            $service->setDefaultValue('mail_greeting', __('global.mail.greeting'));
            $service->setDefaultValue('mail_salutation', __('global.mail.salutation'));
            $service->setDefaultValue('mail_fromaddress', env('MAIL_FROM_ADDRESS'));
            $service->setDefaultValue('mail_fromname', env('MAIL_FROM_NAME'));
            $service->setDefaultValue('mail_smtp_host', env('MAIL_HOST'));
            $service->setDefaultValue('mail_smtp_port', env('MAIL_PORT', '587'));
            $service->setDefaultValue('mail_smtp_username', env('MAIL_USERNAME'));
            $service->setDefaultValue('mail_smtp_password', env('MAIL_PASSWORD'));
            $service->setDefaultValue('mail_smtp_encryption', env('MAIL_ENCRYPTION'));
            $service->setDefaultValue('mail_smtp_enable', env('MAIL_MAILER') == 'smtp');
            $service->setDefaultValue('mail_domain', env('APP_URL', request()->getSchemeAndHttpHost()));
            $service->setDefaultValue('theme_footer_description', config('app.name') . ' You can modify this text in the settings. Powered By CLIENTXCMS');
            $service->setDefaultValue('theme_home_enabled', true);
            $service->setDefaultValue('theme_switch_mode', 'both');
            $service->setDefaultValue('seo_site_title', ' - ' . setting('app_name'));
            $service->setDefaultValue('theme_home_title_meta', setting('app_name'));
            $service->setDefaultValue('helpdesk_ticket_auto_close_days', 7);
            $service->setDefaultValue('helpdesk_attachments_max_size', 5);
            $service->setDefaultValue('helpdesk_allow_attachments', true);
            $service->setDefaultValue('helpdesk_attachments_allowed_types', 'jpg,jpeg,png,pdf,doc,docx,xls,xlsx');
            $service->setDefaultValue('helpdesk_reopen_days', 7);
            $service->setDefaultValue('billing_mode', InvoiceService::INVOICE);
            $service->setDefaultValue('allow_registration', true);
            $service->setDefaultValue('auto_confirm_registration', false);
            $service->setDefaultValue('allow_reset_password', true);
            $service->setDefaultValue('allow_plus_in_email', true);
            $service->setDefaultValue('force_password_reset', false);
            $service->setDefaultValue('force_login_client', false);
            $service->setDefaultValue('banned_emails', '');
            $service->setDefaultValue('captcha_driver', 'none');
            $service->setDefaultValue('maintenance_enabled', false);
            $service->setDefaultValue('maintenance_message', __('maintenance.in_maintenance_message'));
            $service->setDefaultValue('maintenance_url', '/maintenancebypass/' . md5(\Str::random(12)));
            $service->setDefaultValue('maintenance_button_link', null);
            $service->setDefaultValue('maintenance_button_text', null);
            $service->setDefaultValue('maintenance_button_icon', 'bi bi-box-arrow-up-right');
            $service->setDefaultValue('email_template_image', 'https://clientxcms.com/Themes/CLIENTXCMS/images/emails/support3.png');
            $service->setDefaultValue('email_template_title', 'CLIENTXCMS');
            $service->setDefaultValue('email_template_description', 'You can edit this in email template settings.');

            $this->initImage($service, 'app_logo', 'app_logo', 'resources/global/clientxcms_blue.png');
            $this->initImage($service, 'app_logo_text', 'app_logo_text', 'resources/global/clientxcms_text.png');
            $this->initImage($service, 'app_favicon', 'app_favicon', 'resources/global/favicon.png');
            $this->initImage($service, 'theme_home_image', 'theme_home_image', 'resources/global/home.png');
            $this->initSettings($service);
        } catch (\Throwable $e) {
        }
        $this->loadCards($service);
    }

    protected function initImage(SettingsService $service, string $key, string $setting, string $default): void
    {
        $image = setting($setting);
        if ($image) {
            $service->set($key, \Storage::url($image));
        } else {
            $service->set($key, \Vite::asset($default));
        }
    }

    protected function initSettings(SettingsService $service)
    {
        // $this->app->setLocale(str_replace('_', '-', $service->get('app_default_locale', 'fr_FR')));
        // Carbon::setLocale(config('app.locale'));
        // setlocale(LC_ALL, $service->get('app_default_locale', 'fr_FR'));
        // date_default_timezone_set($service->get('app.timezone', 'Europe/Paris'));
        config(['auth.password_timeout' => $service->get('password_timeout', 10800)]);
    }

    protected function loadCards(SettingsService $service)
    {
        $service->addCard('core', 'admin.settings.core.title', 'admin.settings.core.description', 1);
        $service->addCard('extensions', 'extensions.settings.title', 'extensions.settings.description', 3, null, true, 2, 'bi bi-plugin');
        $service->addCard('security', 'admin.security.title', 'admin.security.description', 4, null, true, 2, 'bi bi-lock');
        $service->addCardItem('core', 'app', 'admin.settings.core.app.title', 'admin.settings.core.app.description', 'bi bi-app-indicator', [SettingsCoreController::class, 'showAppSettings'], Permission::MANAGE_SETTINGS);
        $service->set('app.cron.last_run', setting('app.cron.last_run', null));
        $service->addCardItem('core', 'mail', 'admin.settings.core.mail.title', 'admin.settings.core.mail.description', 'bi bi-envelope-at', [SettingsCoreController::class, 'showEmailSettings'], Permission::MANAGE_SETTINGS);
        $service->addCardItem('core', 'maintenance', 'maintenance.settings.title', 'maintenance.settings.description', 'bi bi-toggle-on', [SettingsCoreController::class, 'showMaintenanceSettings'], Permission::MANAGE_SETTINGS);
        $service->addCardItem('core', 'locales', 'admin.locales.title', 'admin.locales.description', 'bi bi-translate', action([AdminLocalesController::class, 'index']), Permission::MANAGE_SETTINGS);
        $service->addCardItem('security', 'admin', 'admin.admins.title', 'admin.admins.description', 'bi bi-person-badge', route('admin.staffs.index'), 'admin.manage_staff');
        $service->addCardItem('security', 'apikeys', 'admin.api_keys.title', 'admin.api_keys.subheading', 'bi bi-key', action([ApiKeysController::class, 'index']), 'admin.manage_api_keys');
        $service->addCardItem('core', 'license', 'admin.license.title', 'admin.license.subheading', 'bi bi-key', action([LicenseController::class, 'index']), 'admin.manage_license');
        $service->addCardItem('security', 'database', 'admin.database.title', 'admin.database.description', 'bi bi-database', action([DatabaseController::class, 'index']), 'admin.manage_database');
        $service->addCardItem('security', 'update', 'admin.update.title', 'admin.update.subheading', 'bi bi-cloud-arrow-up-fill', action([UpdateController::class, 'index']), 'admin.manage_update');
        $service->addCardItem('security', 'security', 'admin.settings.core.security.title', 'admin.settings.core.security.description', 'bi bi-shield-lock', [SettingsSecurityController::class, 'showSecuritySettings'], Permission::MANAGE_SETTINGS);
        $service->addCardItem('security', 'security_questions', 'admin.security_questions.title', 'admin.security_questions.description', 'bi bi-question-circle', route('admin.security_questions.index'), Permission::MANAGE_SETTINGS);
        $service->addCardItem('extensions', 'extensions', 'extensions.title', 'extensions.description', 'bi bi-palette2', [SettingsExtensionController::class, 'showExtensions'], Permission::MANAGE_EXTENSIONS);
        $service->addCardItem('security', 'history', 'admin.history.title', 'admin.history.description', 'bi bi-archive', action([HistoryController::class, 'index']), 'admin.show_logs');
        $service->addCardItem('security', 'logs', 'actionslog.settings.title', 'actionslog.settings.description', 'bi bi-clock', action([ActionsLogController::class, 'index']), 'admin.show_logs');
        $this->app['extension']->addAdminMenuItem((new AdminMenuItem('settings', 'admin.settings.index', 'bi bi-gear', 'admin.settings.title', 100, \App\Models\Admin\Permission::ALLOWED)));
    }

    protected function loadSettings(): array
    {
        if ($this->app->runningInConsole()) {
            return Setting::all()->pluck('value', 'name')->all();
        }

        return Cache::remember('settings', now()->addDay(), function () {
            return Setting::all()->pluck('value', 'name')->all();
        });
    }
}

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
 * Year: 2025
 */
namespace App\DTO\Provisioning;

use App\Models\Provisioning\Service;

class ProvisioningTabDTO
{
    /**
     * @var string - UUID of the tab (for url)
     */
    public string $uuid;

    /**
     * @var bool - Is the tab active for current service
     */
    public bool $active = false;

    /**
     * @var bool - Is the tab a popup (NOVNC example)
     */
    public bool $popup = false;

    /**
     * @var string - Title of the tab
     */
    public string $title;

    /**
     * @var string - Permission required to access the tab
     */
    public string $permission;

    /**
     * @var string - Icon of the tab
     */
    public string $icon;

    /**
     * @var string - URL of the tab
     */
    public ?string $url = null;

    public bool $admin = false;

    public bool $newwindow = false;

    public function __construct(array $data = [])
    {
        if (! empty($data)) {
            $this->uuid = $data['uuid'];
            $this->active = $data['active'];
            $this->popup = $data['popup'] ?? false;
            $this->title = $data['title'];
            $this->permission = $data['permission'];
            $this->icon = $data['icon'];
            $this->url = $data['url'] ?? null;
            $this->admin = $data['admin'] ?? false;
            $this->newwindow = $data['newwindow'] ?? false;
        }
    }

    public function route(int $serviceId, bool $admin = false): string
    {
        if ($this->uuid == 'services') {
            if ($admin) {
                return route('admin.services.show', ['service' => $serviceId]);
            }

            return route('front.services.show', ['service' => $serviceId]);
        }
        if ($admin) {
            return route('admin.services.tab', ['tab' => $this->uuid, 'service' => $serviceId]);
        }

        return route('front.services.tab', ['tab' => $this->uuid, 'service' => $serviceId]);
    }

    public function renderTab(Service $service, bool $inAdmin = false)
    {
        try {
            $panel = $service->productType()->panel();
            if ($panel === null) {
                return '';
            }
            $tabs = $panel->tabs($service);
            if (! empty($tabs)) {
                array_unshift($tabs, new ProvisioningTabDTO([
                    'title' => __('global.service'),
                    'permission' => 'service.show',
                    'icon' => '<i class="bi bi-info-circle"></i>',
                    'uuid' => 'services',
                    'active' => true,
                ]));
                if (! $inAdmin) {
                    $tabs = array_filter($tabs, function ($tab) {
                        return ! $tab->admin;
                    });
                }
            }
            if (! $inAdmin && ! $service->isActivated()) {
                \View::share('tabs', []);
                \View::share('current_tab', null);

                return '';
            }
            \View::share('tabs', $tabs);
            \View::share('current_tab', $this);
            $method = 'render'.ucfirst($this->uuid);
            if (method_exists($panel, $method.'Admin') && $inAdmin) {
                return $panel->$method($service, [], $inAdmin);
            }
            if (method_exists($panel, $method)) {
                return $panel->$method($service, [], $inAdmin);
            }

            return 'Tab not found';
        } catch (\Exception $e) {
            return $inAdmin ? $e->getMessage() : __('client.alerts.internalerror');
        }
    }

    public function canRender(Service $service, bool $inAdmin = false)
    {
        if ($inAdmin && ! $this->admin || ! $this->active) {
            return false;
        }

        return true;
    }

    public static function renderPanel(Service $service, bool $inAdmin = false)
    {
        try {
            $method = 'render';
            if ($inAdmin) {
                $method .= 'Admin';
            }
            $panel = $service->productType()->panel();
            if ($panel === null) {
                \View::share('tabs', []);
                \View::share('current_tab', null);

                return '';
            }
            if (! $inAdmin && ! $service->isActivated()) {
                \View::share('tabs', []);
                \View::share('current_tab', null);

                return '';
            }
            \View::share('tabs', $panel->tabs($service));
            \View::share('current_tab', null);
            if (method_exists($panel, $method)) {
                return $panel->$method($service);
            }

            return 'Tab not found';
        } catch (\Exception $e) {
            return $inAdmin ? $e->getMessage() : __('client.alerts.internalerror');
        }
    }
}

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
use App\Models\Provisioning\CancellationReason;
use App\Models\Provisioning\Service;
use Illuminate\Http\Request;

class CancellationReasonController extends AbstractCrudController
{
    protected string $model = CancellationReason::class;

    protected string $viewPath = 'admin.provisioning.cancellation_reasons';

    protected string $routePath = 'admin.cancellation_reasons';

    protected string $translatePrefix = 'provisioning.admin.cancellation_reasons';

    protected ?string $managedPermission = 'admin.manage_services';

    protected string $searchField = 'reason';

    protected function getIndexFilters(): array
    {
        return [
            'active' => __('global.states.active'),
            'hidden' => __('global.states.hidden'),
            'unreferenced' => __('global.states.unreferenced'),
        ];
    }

    public function index(Request $request)
    {
        $this->checkPermission('showAny');

        if ($request->has('q')) {
            $items = $this->search($request);
            if ($request->ajax()) {
                return $items;
            }
            if (count($items) == 1) {
                return redirect()->route($this->routePath . '.show', $items->first());
            }
        } else {
            $items = $this->queryIndex();
            if ($items->currentPage() > $items->lastPage()) {
                return redirect()->route($this->routePath . '.index', array_merge(request()->query(), ['page' => $items->lastPage()]));
            }
        }

        $startDate = $request->get('start_date', now()->subMonths(6)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $stats = Service::selectRaw('cancelled_reason, COUNT(*) as count')
            ->whereNotNull('cancelled_reason')
            ->whereNotNull('cancelled_at')
            ->whereBetween('cancelled_at', [$startDate, $endDate . ' 23:59:59'])
            ->groupBy('cancelled_reason')
            ->get();

        $reasons = CancellationReason::all()->keyBy('id');

        $chartData = [
            'labels' => [],
            'data' => [],
            'colors' => [],
        ];

        $colors = ['#ef4444', '#f97316', '#eab308', '#22c55e', '#06b6d4', '#3b82f6', '#8b5cf6', '#ec4899'];
        $i = 0;

        foreach ($stats as $stat) {
            $reason = $reasons->get($stat->cancelled_reason);
            $chartData['labels'][] = $reason ? $reason->reason : __('global.unknown');
            $chartData['data'][] = $stat->count;
            $chartData['colors'][] = $colors[$i % count($colors)];
            $i++;
        }

        $cancelledServices = Service::with(['customer', 'product'])
            ->whereNotNull('cancelled_reason')
            ->whereNotNull('cancelled_at')
            ->whereBetween('cancelled_at', [$startDate, $endDate . ' 23:59:59'])
            ->orderBy('cancelled_at', 'desc')
            ->limit(10)
            ->get();

        $params = $this->getIndexParams($items, $this->translatePrefix ?? $this->viewPath);
        $params['chartData'] = $chartData;
        $params['cancelledServices'] = $cancelledServices;
        $params['reasons'] = $reasons;
        $params['startDate'] = $startDate;
        $params['endDate'] = $endDate;
        $params['totalCancellations'] = $stats->sum('count');

        return view($this->viewPath . '.index', $params);
    }

    public function show(CancellationReason $cancellationReason)
    {
        $this->checkPermission('show');
        $params['item'] = $cancellationReason;
        $params['usageCount'] = Service::where('cancelled_reason', $cancellationReason->id)->count();

        return $this->showView($params);
    }

    public function store(Request $request)
    {
        $this->checkPermission('create');
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'status' => 'required|in:active,hidden,unreferenced',
        ]);

        $reason = CancellationReason::create($validated);

        return $this->storeRedirect($reason);
    }

    public function update(CancellationReason $cancellationReason, Request $request)
    {
        $this->checkPermission('update');
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'status' => 'required|in:active,hidden,unreferenced',
        ]);

        $cancellationReason->update($validated);

        return $this->updateRedirect($cancellationReason);
    }

    public function destroy(CancellationReason $cancellationReason)
    {
        $this->checkPermission('delete');
        $cancellationReason->delete();

        return $this->destroyRedirect($cancellationReason);
    }
}

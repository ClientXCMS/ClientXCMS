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


namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Admin\AbstractCrudController;
use App\Models\ActionLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\QueryBuilder;

class ActionsLogController extends AbstractCrudController
{
    protected string $model = ActionLog::class;

    protected string $routePath = 'admin.logs';

    protected string $viewPath = 'admin.core.actionslog';

    protected string $translatePrefix = 'actionslog.settings';

    protected int $perPage = 50;

    protected string $filterField = 'action';

    protected ?string $managedPermission = 'admin.show_logs';

    public function show(ActionLog $log)
    {
        $this->checkPermission('show');

        return $this->showView([
            'item' => $log,
        ]);
    }

    protected function getSearchFields(): array
    {
        return [
            'id' => 'ID',
            'model' => 'Model name',
            'model_id' => 'Model ID',
            'old_value' => 'Old value',
            'new_value' => 'New value',
            'staff_id' => 'Staff ID',
            'customer_id' => 'Customer ID',
        ];
    }

    protected function getIndexFilters(): array
    {
        return collect(ActionLog::ALL_ACTIONS)->mapWithKeys(function ($action) {
            return [$action => ucfirst(str_replace('_', ' ', $action))];
        })->toArray();
    }

    protected function queryIndex(): LengthAwarePaginator
    {
        return QueryBuilder::for($this->model)
            ->allowedFilters(array_merge(array_keys($this->getSearchFields()), [$this->filterField]))
            ->allowedSorts($this->sorts)
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage)
            ->appends(request()->query());
    }
}

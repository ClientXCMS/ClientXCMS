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

namespace App\Http\Controllers\Admin;

use App\Events\Resources\ResourceCreatedEvent;
use App\Events\Resources\ResourceDeletedEvent;
use App\Events\Resources\ResourceUpdatedEvent;
use App\Http\Controllers\Controller;
use App\Models\Admin\Permission;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

abstract class AbstractCrudController extends Controller
{
    protected string $viewPath;

    protected string $routePath;

    protected string $model;

    protected array $flashs = [
        'created' => 'admin.flash.created',
        'updated' => 'admin.flash.updated',
        'deleted' => 'admin.flash.deleted',
    ];

    protected int $perPage = 25;

    protected string $searchField = 'id';

    protected string $filterField = 'status';

    protected bool $extensionPermission = false;

    protected ?string $managedPermission = null;

    protected array $filters = [];

    protected array $sorts = [];

    protected array $relations = [];

    public function index(Request $request)
    {
        $this->checkPermission('showAny');
        if ($request->has('q')) {
            $items = $this->search($request);
            if ($request->ajax()) {
                return $items;
            }
            if (count($items) == 1) {
                return redirect()->route($this->routePath.'.show', $items->first());
            }

            return view($this->viewPath.'.index', $this->getIndexParams($items, $this->translatePrefix ?? $this->viewPath));
        }
        try {
            $items = $this->queryIndex();
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
        if ($items->currentPage() > $items->lastPage()) {
            return redirect()->route($this->routePath.'.index', array_merge(request()->query(), ['page' => $items->lastPage()]));
        }

        return view($this->viewPath.'.index', $this->getIndexParams($items, $this->translatePrefix ?? $this->viewPath));
    }

    protected function getSearchFields()
    {
        return [];
    }

    protected function queryIndex(): LengthAwarePaginator
    {
        $allowedFilters = $this->getAllowedSearchFilters();
        if (! in_array($this->filterField, $allowedFilters, true)) {
            $allowedFilters[] = $this->filterField;
        }

        return QueryBuilder::for($this->model)
            ->allowedFilters($allowedFilters)
            ->allowedSorts($this->sorts)
            ->with($this->relations)
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage)
            ->appends(request()->query());
    }

    private function getCheckedFilters()
    {
        $filters = \request()->query('filter', []);
        if (! is_array($filters)) {
            $filters = [$this->filterField => $filters];
        }
        $checkedFilters = [];
        $values = array_keys($this->getIndexFilters());
        foreach ($filters as $field => $value) {
            $_values = explode(',', $value);
            foreach ($_values as $_value) {
                if (in_array($_value, $values)) {
                    $checkedFilters[] = $_value;
                }
            }
        }

        return $checkedFilters;
    }

    private function getSearchValue()
    {
        $values = $this->getSearchValues();

        return collect($values)->first(fn ($value) => ! blank($value));
    }

    private function getSearchField()
    {
        $filters = \request()->query('filter', []);
        if (! is_array($filters)) {
            return array_key_first($this->getSearchFields());
        }

        foreach ($this->getNormalizedSearchFields() as $key => $definition) {
            foreach ($definition['fields'] as $field) {
                if (! blank($filters[$field] ?? null)) {
                    return $key;
                }
            }
        }

        return array_key_first($this->getSearchFields());
    }

    protected function getNormalizedSearchFields(): array
    {
        $definitions = array_merge($this->getSearchFields(), $this->getDateRangeSearchFields());

        return collect($definitions)->mapWithKeys(function ($definition, $key) {
            if (! is_array($definition)) {
                $definition = ['label' => $definition, 'type' => 'text'];
            }

            $type = $definition['type'] ?? 'text';
            $fields = $definition['fields'] ?? [$key];

            return [$key => [
                'label' => $definition['label'] ?? $key,
                'type' => in_array($type, ['text', 'select', 'date', 'date_range'], true) ? $type : 'text',
                'fields' => array_values((array) $fields),
                'options' => $definition['options'] ?? [],
                'column' => $definition['column'] ?? $key,
            ]];
        })->all();
    }

    protected function getDateRangeSearchFields(): array
    {
        return [
            'created_at_range' => [
                'label' => __('global.created'),
                'type' => 'date_range',
                'column' => 'created_at',
                'fields' => ['date_from', 'date_to'],
            ],
        ];
    }

    protected function getAllowedSearchFilters(): array
    {
        return collect($this->getNormalizedSearchFields())->flatMap(function ($definition) {
            if ($definition['type'] === 'date_range') {
                return [
                    AllowedFilter::callback($definition['fields'][0], function (Builder $query, mixed $value) use ($definition) {
                        if ($date = $this->parseSearchDate($value)) {
                            $query->where($definition['column'], '>=', $date->startOfDay());
                        }
                    }),
                    AllowedFilter::callback($definition['fields'][1], function (Builder $query, mixed $value) use ($definition) {
                        if ($date = $this->parseSearchDate($value)) {
                            $query->where($definition['column'], '<=', $date->endOfDay());
                        }
                    }),
                ];
            }

            if ($definition['type'] === 'date') {
                return [AllowedFilter::callback($definition['fields'][0], function (Builder $query, mixed $value) use ($definition) {
                    if ($date = $this->parseSearchDate($value)) {
                        $query->whereBetween($definition['column'], [$date->startOfDay(), $date->endOfDay()]);
                    }
                })];
            }

            return $definition['fields'];
        })->unique(fn ($filter) => is_string($filter) ? $filter : spl_object_id($filter))->values()->all();
    }

    private function parseSearchDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function getSearchFilterFields(): array
    {
        return collect($this->getNormalizedSearchFields())->pluck('fields')->flatten()->unique()->values()->all();
    }

    private function getSearchValues(): array
    {
        $filters = \request()->query('filter', []);
        if (! is_array($filters)) {
            return [];
        }

        return collect($this->getSearchFilterFields())
            ->mapWithKeys(fn ($field) => [$field => $filters[$field] ?? null])
            ->all();
    }

    protected function getMassActions()
    {
        return [];
    }

    public function create(Request $request)
    {
        $this->checkPermission('create');

        return view($this->viewPath.'.create', $this->getCreateParams());
    }

    public function showView(array $params)
    {
        $params['viewPath'] = $this->viewPath;
        $params['routePath'] = $this->routePath;
        $params['translatePrefix'] = $this->translatePrefix ?? $this->viewPath;

        return view($this->viewPath.'.show', $params);
    }

    protected function getIndexParams($items, string $translatePrefix)
    {
        $data['items'] = $items;
        $data['translatePrefix'] = $translatePrefix;
        $data['viewPath'] = $this->viewPath;
        $data['routePath'] = $this->routePath;
        $data['checkedFilters'] = $this->getCheckedFilters();
        $data['searchFields'] = $this->getSearchFields();
        $data['searchDefinitions'] = $this->getNormalizedSearchFields();
        $data['searchValues'] = $this->getSearchValues();
        $data['filterField'] = $this->filterField;
        $data['filters'] = $this->getIndexFilters();
        $data['search'] = $this->getSearchValue();
        $data['searchField'] = $this->getSearchField();
        $data['perPage'] = $this->perPage;
        $data['mass_actions'] = $this->getMassActions();

        return $data;
    }

    public function massAction(Request $request)
    {
        $this->checkPermission('update');
        $massAction = collect($this->getMassActions())->firstWhere('action', $request->get('action'));
        if ($massAction == null) {
            abort(404);
        }
        $ids = $request->get('ids', '');
        $ids = explode(',', $ids);
        if (empty($ids)) {
            return back()->with('errors', [__('admin.flash.no_items_selected')]);
        }
        $massAction->ids = $ids;
        $massAction->setResponse($ids, $request->get('input') ?? null);
        [$success, $errors] = $massAction->execute($this->model);

        return back()->with('success', __('admin.flash.updated_elements', ['counts' => count($success)]))->with('errors', implode(',', $errors));
    }

    protected function getCreateParams()
    {
        $data = [];
        $data['item'] = new $this->model;
        $data['viewPath'] = $this->viewPath;
        $data['routePath'] = $this->routePath;
        $data['translatePrefix'] = $this->translatePrefix ?? $this->viewPath;

        return $data;
    }

    public function createView(array $params)
    {
        $params['viewPath'] = $this->viewPath;
        $params['routePath'] = $this->routePath;
        $params['translatePrefix'] = $this->translatePrefix ?? $this->viewPath;
        if (! isset($params['item'])) {
            $params['item'] = new $this->model;
        }

        return view($this->viewPath.'.create', $params);
    }

    protected function getIndexFilters()
    {
        return [];
    }

    /**
     * @deprecated use queryIndex instead
     *
     * @return mixed
     */
    protected function filterIndex(string $filter)
    {
        return $this->model::orderBy('created_at', 'desc')->where($this->filterField, $filter)->paginate($this->perPage);
    }

    protected function search(Request $request)
    {
        if (empty($this->searchField) || empty($request->get('q'))) {
            return $this->model::orderBy('created_at', 'desc')->paginate($this->perPage);
        }

        return $this->model::whereLike($this->searchField, $request->get('q'))->paginate($this->perPage);
    }

    protected function updateRedirect(Model $model)
    {
        event(new ResourceUpdatedEvent($model));

        return back()->with('success', __($this->flashs['updated']));
    }

    protected function storeRedirect(Model $model)
    {
        event(new ResourceCreatedEvent($model));

        return redirect()->route($this->routePath.'.show', [$model->id])->with('success', __($this->flashs['created']));
    }

    protected function deleteRedirect(Model $model)
    {
        event(new ResourceDeletedEvent($model));

        return redirect()->route($this->routePath.'.index')->with('success', __($this->flashs['deleted']));
    }

    protected function destroyRedirect(Model $model)
    {
        event(new ResourceDeletedEvent($model));

        return redirect()->route($this->routePath.'.index')->with('success', __($this->flashs['deleted']));
    }

    protected function checkPermission(string $action, ?Model $model = null)
    {
        $table = $model ? $model->getTable() : $this->model::make()->getTable();
        $permission = $this->getPermissions($table);
        $permissions = $permission[$action] ?? $action;
        if ($this->beforePermissionCheck($model)) {
            return;
        }
        foreach ((array) $permissions as $perm) {
            if (! staff_has_permission($perm)) {
                abort(403);
            }
        }
        $this->afterPermissionCheck($model);
    }

    protected function getPermissions(string $tablename)
    {
        return [
            'showAny' => [
                'admin.show_'.$tablename,
            ],
            'show' => [
                'admin.show_'.$tablename,
            ],
            'update' => [
                'admin.manage_'.$tablename,
            ],
            'delete' => [
                'admin.manage_'.$tablename,
            ],
            'create' => [
                'admin.manage_'.$tablename,
            ],
        ];
    }

    protected function beforePermissionCheck(?Model $model = null)
    {
        if ($this->extensionPermission && staff_has_permission(Permission::MANAGE_EXTENSIONS)) {
            return true;
        }
        if ($this->managedPermission != null && staff_has_permission($this->managedPermission)) {
            return true;
        }

        return false;
    }

    protected function afterPermissionCheck(?Model $model = null) {}
}

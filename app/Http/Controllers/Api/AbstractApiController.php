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
namespace App\Http\Controllers\Api;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class AbstractApiController
{
    protected string $model;

    protected int $perPage = 25;

    protected array $sorts = [];

    protected array $relations = [];

    protected array $filters = [];

    protected function queryIndex(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for($this->model)
            ->allowedFilters($this->filters)
            ->allowedSorts($this->sorts)
            ->allowedIncludes($this->relations)
            ->paginate($this->getPerPage($request))
            ->appends(request()->query());
    }

    private function getPerPage(Request $request): int
    {
        return (int) $request->input('per_page', $this->perPage);
    }

    protected function queryShow($id): LengthAwarePaginator
    {
        return QueryBuilder::for($this->model)
            ->allowedFilters($this->filters)
            ->allowedSorts($this->sorts)
            ->allowedIncludes($this->relations)
            ->where('id', $id)
            ->paginate($this->getPerPage(request()))
            ->appends(request()->query());
    }
}

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
use App\Models\Admin\Permission;
use App\Models\Admin\SecurityQuestion;
use Illuminate\Http\Request;

class SecurityQuestionController extends AbstractCrudController
{
    protected string $viewPath = 'admin.security.security_questions';

    protected string $routePath = 'admin.security_questions';

    protected string $translatePrefix = 'admin.security_questions';

    protected string $model = SecurityQuestion::class;

    protected string $searchField = 'question';

    protected string $filterField = 'is_active';

    protected ?string $managedPermission = Permission::MANAGE_SETTINGS;

    public function __construct()
    {
        $this->shareSettingsCard();
    }

    protected function shareSettingsCard(): void
    {
        $card = app('settings')->getCards()->firstWhere('uuid', 'security');
        if ($card) {
            $item = $card->items->firstWhere('uuid', 'security_questions');
            \View::share('current_card', $card);
            \View::share('current_item', $item);
        }
    }

    protected function getIndexFilters(): array
    {
        return [
            '1' => __('global.states.active'),
            '0' => __('global.states.inactive'),
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
                return redirect()->route($this->routePath.'.show', $items->first());
            }
        } else {
            $items = SecurityQuestion::orderBy('sort_order')
                ->orderBy('id')
                ->paginate($this->perPage);

            if ($items->currentPage() > $items->lastPage()) {
                return redirect()->route($this->routePath.'.index', array_merge(request()->query(), ['page' => $items->lastPage()]));
            }
        }

        return view($this->viewPath.'.index', $this->getIndexParams($items, $this->translatePrefix));
    }

    public function create(Request $request)
    {
        $this->checkPermission('create');

        return $this->createView([]);
    }

    public function store(Request $request)
    {
        $this->checkPermission('create');

        $data = $request->validate([
            'question' => 'required|string|max:255',
            'is_active' => 'nullable',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $data['is_active'] = $request->has('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $item = SecurityQuestion::create($data);

        return $this->storeRedirect($item);
    }

    public function show(SecurityQuestion $security_question)
    {
        $this->checkPermission('show');

        return $this->showView([
            'item' => $security_question,
        ]);
    }

    public function update(Request $request, SecurityQuestion $security_question)
    {
        $this->checkPermission('update');

        $data = $request->validate([
            'question' => 'required|string|max:255',
            'is_active' => 'nullable',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $data['is_active'] = $request->has('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $security_question->update($data);

        return $this->updateRedirect($security_question);
    }

    public function destroy(SecurityQuestion $security_question)
    {
        $this->checkPermission('delete');

        $security_question->delete();

        return $this->destroyRedirect($security_question);
    }
}

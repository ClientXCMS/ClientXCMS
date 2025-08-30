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


namespace App\Http\Controllers\Admin\Helpdesk\Support;

use App\Http\Controllers\Admin\AbstractCrudController;
use App\Http\Requests\Helpdesk\StoreDepartmentRequest;
use App\Http\Requests\Helpdesk\UpdateDepartmentRequest;
use App\Models\Helpdesk\SupportDepartment;

class DepartmentController extends AbstractCrudController
{
    protected string $viewPath = 'admin.helpdesk.departments';

    protected string $routePath = 'admin.helpdesk.departments';

    protected string $translatePrefix = 'helpdesk.admin.departments';

    protected string $model = SupportDepartment::class;

    protected ?string $managedPermission = 'admin.manage_departments';

    public function store(StoreDepartmentRequest $request)
    {
        $this->checkPermission('create');
        $department = SupportDepartment::create($request->all());

        return $this->storeRedirect($department);
    }

    public function show(SupportDepartment $department)
    {
        $this->checkPermission('show');

        return $this->showView(['item' => $department]);
    }

    public function update(UpdateDepartmentRequest $request, SupportDepartment $department)
    {
        $this->checkPermission('update');
        $department = $request->update();

        return $this->updateRedirect($department);
    }

    public function destroy(SupportDepartment $department)
    {
        $this->checkPermission('delete');
        if ($department->tickets()->count() > 0) {
            return back()->with('error', __('helpdesk.admin.departments.error_delete'));
        }
        $department->delete();

        return $this->deleteRedirect($department);
    }

    protected function getPermissions(string $tablename)
    {
        $tablename = 'helpdesk_departments';

        return parent::getPermissions($tablename);
    }
}

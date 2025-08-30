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
namespace App\Observers;

use App\Models\Admin\Permission;
use App\Models\Helpdesk\SupportDepartment;

class SupportDepartmentObserver
{
    public function created(SupportDepartment $department)
    {
        Permission::create([
            'name' => "admin.manage_tickets_department.{$department->id}",
            'group' => 'permissions.helpdesk',
            'label' => 'permissions.manage_tickets_department',
        ]);
    }

    public function updating(SupportDepartment $department)
    {
        Permission::updateOrCreate([
            'name' => "admin.manage_tickets_department.{$department->id}",
            'group' => 'permissions.helpdesk',
            'label' => 'permissions.manage_tickets_department',
        ]);
    }

    public function deleted(SupportDepartment $department)
    {
        Permission::where('name', "admin.manage_tickets_department.{$department->id}")->delete();
    }
}

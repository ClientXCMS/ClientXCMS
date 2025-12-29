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

use App\DTO\Core\Extensions\ExtensionDTO;
use App\Http\Controllers\Controller;
use App\Models\Admin\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

class DatabaseController extends Controller
{
    public function index()
    {
        staff_aborts_permission(Permission::MANAGE_DATABASE);
        $extensions = collect(app('extension')->getAllExtensions())->filter(function (ExtensionDTO $extension) {
            return $extension->supportMigration();
        })->mapWithKeys(function (ExtensionDTO $extension) {
            return [$extension->uuid => $extension->name()];
        })->toArray();
        $extensions['core'] = 'Core';
        $card = app('settings')->getCards()->firstWhere('uuid', 'security');
        if (! $card) {
            abort(404);
        }
        $item = $card->items->firstWhere('uuid', 'database');
        \View::share('current_card', $card);
        \View::share('current_item', $item);

        return view('admin.settings.core.database', compact('extensions'));
    }

    public function migrate(Request $request)
    {
        staff_aborts_permission(Permission::MANAGE_DATABASE);
        $extension = $request->input('extension');
        $output = new BufferedOutput;
        if ($request->has('seed')) {
            Artisan::call('db:seed', ['--force' => true], $output);

            return back()->with('success', __('admin.database.seedsuccess'))->with('output', $output->fetch());
        }
        if ($extension == 'core') {
            \Artisan::call('migrate', ['--force' => true], $output);

            return back()->with('success', __('admin.database.migratesuccess'))->with('output', $output->fetch());
        }
        \Artisan::call('clientxcms:db-extension', ['--extension' => $extension], $output);

        return back()->with('success', __('admin.database.migratesuccess'))->with('output', $output->fetch());
    }
}

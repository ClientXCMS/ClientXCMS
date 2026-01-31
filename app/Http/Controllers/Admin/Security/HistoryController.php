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

use App\Http\Controllers\Controller;
use App\Services\Core\LogsReaderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        staff_aborts_permission('admin.show_logs');
        $reader = new LogsReaderService;
        $folderFiles = [];
        try {

            if ($request->input('f')) {
                $reader->setFolder(Crypt::decrypt($request->input('f')));
                $folderFiles = $reader->getFolderFiles(true);
            }

            if ($request->input('l')) {
                $reader->setFile(Crypt::decrypt($request->input('l')));
            }
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
        $data = [
            'folders' => $reader->getFolders(),
            'current_folder' => $reader->getFolderName(),
            'folder_files' => $folderFiles,
            'files' => $reader->getFiles(true),
            'current_file' => $reader->getFileName(),
            'standardFormat' => true,
            'structure' => $reader->foldersAndFiles(),
            'storage_path' => $reader->getStoragePath(),
            'content' => $reader->get(),
        ];

        if ($request->wantsJson()) {
            return $data;
        }

        return view('admin.dashboard.history.index', $data);
    }

    public function download(Request $request)
    {
        staff_aborts_permission('admin.show_logs');
        if (! $request->input('dl')) {
            return back()->with('error', 'Invalid download link');
        }
        $file = Crypt::decrypt($request->input('dl'));

        return response()->download((new LogsReaderService)->pathToLogFile($file));
    }
}

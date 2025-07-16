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
use App\Http\Controllers\Admin\Helpdesk\HelpdeskSettingsController;
use App\Http\Controllers\Admin\Helpdesk\Support\DepartmentController;
use App\Http\Controllers\Admin\Helpdesk\Support\TicketController;
use Illuminate\Support\Facades\Route;

Route::name('helpdesk.')->prefix('/helpdesk')->group(function () {
    Route::delete('/tickets/{ticket}/messages/{message}/delete', [TicketController::class, 'destroyMessage'])->name('tickets.messages.destroy');
    Route::post('/tickets/{ticket}/messages/{message}/update', [TicketController::class, 'updateMessage'])->name('tickets.messages.update');
    Route::delete('/tickets/{ticket}/close', [TicketController::class, 'close'])->name('tickets.close');
    Route::post('/tickets/{ticket}/reopen', [TicketController::class, 'reopen'])->name('tickets.reopen');
    Route::get('/tickets/{ticket}/download/{attachment}', [TicketController::class, 'download'])->name('tickets.download');
    Route::post('/tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');
    Route::post('/tickets/{ticket}/comments', [TicketController::class, 'addComment'])->name('tickets.comments');
    Route::delete('/tickets/{ticket}/comments/{comment}', [TicketController::class, 'deleteComment'])->name('tickets.comments.delete');
    Route::resource('/tickets', TicketController::class)->names('tickets')->except('edit');
    Route::resource('/departments', DepartmentController::class)->names('departments')->except('edit');
});

Route::name('settings.')->prefix('settings')->middleware('admin')->group(function () {
    Route::get('/helpdesk/settings', [HelpdeskSettingsController::class, 'showSettings'])->name('helpdesk');
    Route::put('/helpdesk/settings', [HelpdeskSettingsController::class, 'storeSettings']);
});

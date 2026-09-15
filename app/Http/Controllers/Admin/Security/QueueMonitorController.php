<?php

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Controller;
use App\Models\ActionLog;
use App\Models\FailedQueueJob;
use App\Models\QueueJob;
use App\Services\Core\QueueHealthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class QueueMonitorController extends Controller
{
    public function index(Request $request, QueueHealthService $health)
    {
        staff_aborts_permission('admin.show_logs');
        $status = $request->string('status', 'waiting')->toString();
        abort_unless(in_array($status, ['waiting', 'delayed', 'reserved', 'failed'], true), 404);

        $jobs = null;
        if ($health->isManageable()) {
            $query = match ($status) {
                'waiting' => $health->waitingQuery(),
                'delayed' => $health->delayedQuery(),
                'reserved' => $health->reservedQuery(),
                'failed' => $health->failedQuery(),
            };
            $jobs = $query->orderBy($status === 'failed' ? 'failed_at' : 'id', 'desc')->paginate(30)->withQueryString();
        }

        return view('admin.security.queues.index', [
            'health' => $health,
            'summary' => $health->summary(),
            'status' => $status,
            'jobs' => $jobs,
        ]);
    }

    public function action(Request $request, QueueHealthService $health)
    {
        staff_aborts_permission('admin.show_logs');
        abort_unless($health->isManageable(), 409);
        $validated = $request->validate([
            'action' => ['required', Rule::in(['retry_failed', 'delete_failed', 'make_available', 'release', 'delete_pending'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string', 'max:255'],
        ]);

        $count = str_ends_with($validated['action'], '_failed')
            ? $this->handleFailed($validated['action'], $validated['ids'], $health)
            : $this->handlePending($validated['action'], $validated['ids'], $health);

        return back()->with('success', __('admin.queues.actions.completed', ['count' => $count]));
    }

    private function handleFailed(string $action, array $ids, QueueHealthService $health): int
    {
        $jobs = FailedQueueJob::query()->whereIn('uuid', $ids)
            ->where('connection', $health->connection())->where('queue', $health->queue())->get();

        foreach ($jobs as $job) {
            if ($action === 'retry_failed') {
                Artisan::call('queue:retry', ['id' => [$job->uuid]]);
            } else {
                $job->delete();
            }
            $this->log($action, $job->uuid);
        }

        return $jobs->count();
    }

    private function handlePending(string $action, array $ids, QueueHealthService $health): int
    {
        $numericIds = collect($ids)->filter(fn ($id) => ctype_digit((string) $id))->map(fn ($id) => (int) $id)->all();

        return DB::transaction(function () use ($action, $numericIds, $health) {
            $jobs = QueueJob::query()->whereIn('id', $numericIds)->where('queue', $health->queue())->lockForUpdate()->get();
            $changed = 0;
            foreach ($jobs as $job) {
                if ($action === 'make_available' && $job->reserved_at !== null) {
                    continue;
                }
                if ($action === 'release' && $job->reserved_at === null) {
                    continue;
                }

                if ($action === 'delete_pending') {
                    $job->delete();
                } else {
                    $job->forceFill(['reserved_at' => null, 'available_at' => now()->timestamp])->save();
                }
                $this->log($action, (string) $job->id);
                $changed++;
            }

            return $changed;
        });
    }

    private function log(string $action, string $id): void
    {
        ActionLog::log(ActionLog::OTHER, QueueJob::class, $id, auth('admin')->id(), null, [
            'message' => "queue:{$action}",
        ]);
    }
}

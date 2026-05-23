<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * v2.16 — Drops a single `bulk()` endpoint into any admin CRUD
 * controller. The action name is dispatched to a `bulk{Action}` method
 * on the consuming controller, e.g. `bulkDelete($ids)`.
 *
 * Why a trait instead of a base class?
 *   * The existing controllers already extend AbstractCrudController.
 *     Introducing a parallel hierarchy would force duplicating it.
 *   * Each consumer can override `bulkActions()` to declare which
 *     actions are exposed, providing per-resource authorisation
 *     without leaking method dispatching to arbitrary input.
 */
trait HandlesBulkActions
{
    /**
     * Bulk endpoint — accepts { action: string, ids: array }.
     *
     * Responses are JSON because the JS driver consumes them via fetch.
     */
    public function bulk(Request $request): JsonResponse
    {
        $request->validate([
            'action' => ['required', 'string', 'max:64'],
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['integer'],
        ]);

        $action = $request->input('action');
        $ids = array_values(array_unique($request->input('ids', [])));

        $allowed = $this->bulkActions();
        if (! array_key_exists($action, $allowed)) {
            return response()->json([
                'message' => 'This bulk action is not allowed for this resource.',
            ], 422);
        }

        $handler = $allowed[$action];

        try {
            return DB::transaction(function () use ($handler, $ids) {
                $count = $handler($ids);

                return response()->json([
                    'message' => $count . ' item(s) processed successfully.',
                    'processed' => $count,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('[v2.16] Bulk action failed: '.$e->getMessage(), [
                'exception' => $e,
                'action' => $action,
                'ids' => $ids,
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Each consumer overrides this map: action key => closure(ids).
     * The closure receives the validated id array and must return the
     * number of records that were actually processed (used in the
     * success message).
     *
     * @return array<string, callable>
     */
    abstract protected function bulkActions(): array;
}

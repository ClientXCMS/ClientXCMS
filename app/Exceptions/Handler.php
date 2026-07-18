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

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\File;
use Illuminate\View\ViewException;
use Symfony\Component\Mailer\Exception\TransportException;
use Throwable;

class Handler extends ExceptionHandler
{
    private const RENDERED_STATUSES = [401, 403, 404, 419, 422, 429, 500, 503];

    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    protected $dontReport = [
        TransportException::class,
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
        });
    }

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ThrottleRequestsException && $request->routeIs('front.profile.export')) {
            $retryAfter = max(1, (int) ($exception->getHeaders()['Retry-After'] ?? 60));

            return redirect()->to(route('front.profile.index').'#pane-export')
                ->with('error', __('client.gdpr.export.throttled', [
                    'minutes' => (int) ceil($retryAfter / 60),
                ]));
        }

        if ($exception instanceof ViewException && \Str::contains($exception->getMessage(), 'Vite manifest not found at')) {
            return response("Vite manifest not found. Please execute 'npm install && npm run build'", 404);
        }
        $status = $this->isHttpException($exception) ? $exception->getStatusCode() : 500;
        $canRenderBrandedPage = in_array($status, self::RENDERED_STATUSES, true)
            && ! $request->expectsJson()
            && ($this->isHttpException($exception) || ! config('app.debug'));

        if ($canRenderBrandedPage) {
            try {
                $data = [
                    'exception' => $exception,
                    'statusCode' => $status,
                ];

                if ($view = $this->errorView($request, $status, $data)) {
                    return response($view, $status);
                }

                return response()->view('errors.'.$status, $data, $status);
            } catch (\Throwable $renderError) {
                logger()->warning('Unable to render the branded error page.', [
                    'status' => $status,
                    'error' => $renderError->getMessage(),
                ]);
            }
        }

        return parent::render($request, $exception);
    }

    private function errorView($request, int $status, array $data): mixed
    {
        if ($this->isAdminRequest($request) && view()->exists('admin.errors.'.$status)) {
            return view('admin.errors.'.$status, $data);
        }

        if ($this->isAdminRequest($request)) {
            return null;
        }

        if ($status === 404) {
            $defaultThemeView = resource_path('themes/default/views/errors/404.blade.php');

            if (File::isFile($defaultThemeView)) {
                return view()->file($defaultThemeView, $data);
            }
        }

        $themeView = app('theme')->themePath("views/errors/{$status}.blade.php");

        return $themeView && File::isFile($themeView)
            ? view()->file($themeView, $data)
            : null;
    }

    private function isAdminRequest($request): bool
    {
        if ($request->routeIs('admin.*')) {
            return true;
        }

        try {
            return $request->is(admin_prefix('*'));
        } catch (\Throwable) {
            return false;
        }
    }

    public function report(Throwable $exception)
    {
        parent::report($exception);
    }
}

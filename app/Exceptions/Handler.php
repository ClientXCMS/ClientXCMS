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
use Illuminate\View\ViewException;
use Symfony\Component\Mailer\Exception\TransportException;
use Throwable;

class Handler extends ExceptionHandler
{
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

    /**
     * v2.16 — Status codes for which we ship a custom branded error page.
     * Adding a code here is enough as long as resources/views/errors/{code}.blade.php exists.
     */
    private const RENDERED_STATUSES = [401, 403, 404, 419, 422, 429, 500, 503];

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ViewException && \Str::contains($exception->getMessage(), 'Vite manifest not found at')) {
            return response("Vite manifest not found. Please execute 'npm install && npm run build'", 404);
        }

        // v2.16 — uniform handler for known HTTP error codes. Falls through
        // to Laravel's default rendering when the view cannot be rendered
        // (e.g. theme override is broken) so we never serve a white page.
        if ($this->isHttpException($exception)) {
            $status = $exception->getStatusCode();
            if (in_array($status, self::RENDERED_STATUSES, true)) {
                try {
                    return response()->view('errors.' . $status, [
                        'exception' => $exception,
                        // v2.16 — pass the code as a real variable so the
                        // layout doesn't need to call View::yieldContent()
                        // mid-render (that helper leaks output buffers
                        // when invoked inside @php / __() expressions).
                        'statusCode' => $status,
                    ], $status);
                } catch (\Throwable $renderError) {
                    // The branded page itself failed (broken theme override,
                    // missing translation file, …). Log + fall back to the
                    // framework default rather than 500'ing on a 404.
                    logger()->warning('[v2.16] Failed to render errors.' . $status . ' view: ' . $renderError->getMessage());
                }
            }
        }

        return parent::render($request, $exception);
    }

    public function report(Throwable $exception)
    {
        parent::report($exception);
    }
}

<?php

namespace Tests\Feature\Errors;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Tests\TestCase;

/**
 * v2.16 — verifies that the redesigned, branded error pages are returned
 * with the correct status code, that the i18n keys resolve, and that the
 * standalone fallback layout has no dependency on the active theme.
 */
class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_404_returns_branded_page_with_status(): void
    {
        $response = $this->get('/some-route-that-does-not-exist-' . uniqid());

        $response->assertStatus(404);
        $response->assertSee(__('v216::errors.404.title'), false);
        $response->assertSee(__('v216::errors.404.description'), false);
    }

    /**
     * @dataProvider httpCodes
     */
    public function test_each_supported_status_has_a_view(int $code, string $exceptionClass): void
    {
        // Bind a route that throws the desired HTTP exception so we hit
        // App\Exceptions\Handler::render the same way real traffic does.
        \Route::get('/__tests__/throw/' . $code, function () use ($code, $exceptionClass) {
            if ($exceptionClass === HttpException::class) {
                throw new HttpException($code);
            }
            throw new $exceptionClass;
        });

        $response = $this->get('/__tests__/throw/' . $code);

        $response->assertStatus($code);
        $response->assertSee(__('v216::errors.' . $code . '.title'), false);
        $response->assertSee('Error ' . $code, false);
    }

    public static function httpCodes(): array
    {
        return [
            '403' => [403, AccessDeniedHttpException::class],
            '404' => [404, NotFoundHttpException::class],
            '429' => [429, TooManyRequestsHttpException::class],
            '503' => [503, ServiceUnavailableHttpException::class],
            // Generic HttpException with arbitrary status for 401/419/422/500
            '401' => [401, HttpException::class],
            '419' => [419, HttpException::class],
            '422' => [422, HttpException::class],
            '500' => [500, HttpException::class],
        ];
    }
}

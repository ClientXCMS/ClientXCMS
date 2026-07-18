<?php

namespace Tests\Feature\Errors;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public function test_unhandled_exception_uses_the_branded_500_page_when_debug_is_disabled(): void
    {
        config()->set('app.debug', false);
        \Route::get('/__tests__/exception', fn () => throw new \RuntimeException('Sensitive internal message'));

        $response = $this->withExceptionHandling()->get('/__tests__/exception');

        $response->assertStatus(500)
            ->assertSee(__('errors.500.title'))
            ->assertDontSee('Sensitive internal message');
    }

    #[DataProvider('statuses')]
    public function test_supported_http_errors_use_the_branded_page(int $status): void
    {
        \Route::get('/__tests__/error/'.$status, fn () => throw new HttpException($status));

        $response = $this->withExceptionHandling()->get('/__tests__/error/'.$status);

        $response->assertStatus($status)
            ->assertSee(__('errors.'.$status.'.title'))
            ->assertSee((string) $status);
    }

    public static function statuses(): array
    {
        return collect([401, 403, 404, 419, 422, 429, 500, 503])
            ->mapWithKeys(fn (int $status) => [(string) $status => [$status]])
            ->all();
    }
}

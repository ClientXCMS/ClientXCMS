<?php

namespace Tests\Feature\Admin\Personalization;

use Tests\TestCase;

class SortRoutesCsrfTest extends TestCase
{
    /**
     * Returns the list of middleware (resolved) attached to a named route.
     * Excludes CSRF when the route was opted out via withoutMiddleware().
     */
    private function effectiveMiddleware(string $name): array
    {
        $route = \Route::getRoutes()->getByName($name);
        $this->assertNotNull($route, "route {$name} must exist");
        $resolved = app(\Illuminate\Contracts\Http\Kernel::class)->getRouteMiddleware();
        $aliases = array_flip(array_map(fn ($v) => is_string($v) ? $v : $v::class, $resolved));

        return collect($route->gatherMiddleware())
            ->merge($route->excludedMiddleware() ?? [])
            ->toArray();
    }

    private function assertCsrfActive(string $routeName): void
    {
        $route = \Route::getRoutes()->getByName($routeName);
        $this->assertNotNull($route, "route {$routeName} must exist");
        $excluded = $route->excludedMiddleware();
        $hasCsrfBypass = collect($excluded)->contains(function ($m) {
            return $m === 'csrf'
                || $m === \App\Http\Middleware\VerifyCsrfToken::class
                || (is_string($m) && str_contains($m, 'CsrfToken'));
        });
        $this->assertFalse(
            $hasCsrfBypass,
            "Route {$routeName} must NOT bypass CSRF: drag-drop JS already sends X-CSRF-TOKEN header"
        );
    }

    public function test_groups_sort_keeps_csrf(): void
    {
        $this->assertCsrfActive('admin.groups.sort');
    }

    public function test_sections_sort_keeps_csrf(): void
    {
        $this->assertCsrfActive('admin.personalization.sections.sort');
    }

    public function test_menulinks_sort_keeps_csrf(): void
    {
        $this->assertCsrfActive('admin.personalization.menulinks.sort');
    }
}

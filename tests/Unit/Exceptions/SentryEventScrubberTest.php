<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\SentryEventScrubber;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Sentry\Event;
use Tests\TestCase;

class SentryEventScrubberTest extends TestCase
{
    public function test_a_token_carried_by_the_path_is_filtered(): void
    {
        $this->pretendWeAreOn('reset-password/{token}', '/reset-password/9f2a-secret-token');

        $event = $this->eventFor('https://shop.test/reset-password/9f2a-secret-token');

        $url = SentryEventScrubber::handle($event)->getRequest()['url'];
        $this->assertStringNotContainsString('9f2a-secret-token', $url, 'A reset token is valid for an hour: it must not reach the error tracker');
        $this->assertSame('https://shop.test/reset-password/[Filtered]', $url);
    }

    public function test_a_signed_verification_link_is_filtered(): void
    {
        $this->pretendWeAreOn('verify-email/{id}/{hash}', '/verify-email/12/9fa1');

        $event = $this->eventFor('https://shop.test/verify-email/12/9fa1?expires=1757942400&signature=deadbeef');

        $request = SentryEventScrubber::handle($event)->getRequest();
        $this->assertSame('https://shop.test/verify-email/12/[Filtered]?expires=1757942400&signature=[Filtered]', $request['url']);
        $this->assertSame('expires=1757942400&signature=[Filtered]', $request['query_string']);
    }

    public function test_an_address_in_the_query_is_filtered(): void
    {
        $event = $this->eventFor('https://shop.test/reset-password?email=customer%40example.com');

        $url = SentryEventScrubber::handle($event)->getRequest()['url'];
        $this->assertStringNotContainsString('customer', $url);
    }

    public function test_an_ordinary_url_is_left_alone(): void
    {
        $this->pretendWeAreOn('store/{product}', '/store/vps-4');

        $event = $this->eventFor('https://shop.test/store/vps-4?page=2&sort=price');

        $request = SentryEventScrubber::handle($event)->getRequest();
        $this->assertSame('https://shop.test/store/vps-4?page=2&sort=price', $request['url']);
        $this->assertSame('page=2&sort=price', $request['query_string']);
    }

    public function test_an_event_without_a_request_is_left_alone(): void
    {
        $event = Event::createEvent();

        $this->assertSame([], SentryEventScrubber::handle($event)->getRequest());
    }

    public function test_the_configured_callback_is_callable(): void
    {
        $this->assertIsCallable(config('sentry.before_send'), 'A before_send that is not callable is dropped by the SDK without a word');
    }

    private function eventFor(string $url): Event
    {
        $query = parse_url($url, PHP_URL_QUERY);

        return Event::createEvent()->setRequest(array_filter([
            'url' => $url,
            'method' => 'GET',
            'query_string' => $query,
        ]));
    }

    private function pretendWeAreOn(string $uri, string $path): void
    {
        $request = Request::create($path);
        $route = (new Route(['GET'], $uri, static fn () => null))->bind($request);
        $request->setRouteResolver(static fn () => $route);

        $this->app->instance('request', $request);
    }
}

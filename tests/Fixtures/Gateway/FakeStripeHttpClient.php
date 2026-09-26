<?php

namespace Tests\Fixtures\Gateway;

use Stripe\HttpClient\ClientInterface;

class FakeStripeHttpClient implements ClientInterface
{
    public array $requests = [];

    public function __construct(private array $body) {}

    public function request($method, $absUrl, $headers, $params, $hasFile)
    {
        $this->requests[] = [$method, $absUrl];

        return [json_encode($this->body), 200, []];
    }
}

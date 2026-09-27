<?php

namespace Tests\Fixtures\Gateway;

use Mollie\Api\HttpAdapter\MollieHttpAdapterInterface;

class FakeMollieHttpAdapter implements MollieHttpAdapterInterface
{
    public function __construct(private array $payment) {}

    public function send($httpMethod, $url, $headers, $httpBody)
    {
        return json_decode(json_encode($this->payment));
    }

    public function versionString()
    {
        return 'Fake/1.0';
    }
}

<?php

namespace Tests\Support\Domain;

use Closure;

/** Records every API method called by a registrar and answers through a module-specific responder. */
final class FakeRegistrarApi
{
    /** @var array<int, string> */
    public array $calls = [];

    /** @var array<string, array<int, array>> Arguments of each call, grouped by method. */
    public array $arguments = [];

    public function __construct(private readonly Closure $responder) {}

    public function __call(string $method, array $arguments): mixed
    {
        $this->calls[] = $method;
        $this->arguments[$method][] = $arguments;

        return ($this->responder)($method, $arguments);
    }
}

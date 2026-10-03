<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Support;

use Closure;
use Throwable;

abstract class ReflectionPestTestCase
{
    protected function assertThrows(Closure $test, string|Closure $expectedClass = Throwable::class, ?string $expectedMessage = null): void
    {
    }
}

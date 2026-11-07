<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Support;

use Closure;
use LogicException;

abstract class ReflectionClosureFactories
{
    abstract public function abstractFactory(): Closure;

    public function arrowFactory(): Closure
    {
        return static fn (string $key, string ...$forms): string => $key;
    }

    public function dynamicFactory(): Closure
    {
        $value = 'category_id';

        return static fn (): string => $value;
    }

    public function nonClosureFactory(): string
    {
        return 'category_id';
    }

    public function throwingFactory(): never
    {
        throw new LogicException('A formatter must never invoke a closure factory.');
    }
}

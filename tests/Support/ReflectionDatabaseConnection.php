<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Support;

use Closure;

final class ReflectionDatabaseConnection
{
    public function selectOne(string $query, array $bindings = [], bool $useReadPdo = true): ?object
    {
        return null;
    }

    public function write(string $query, array $bindings, object $context): object
    {
        return $context;
    }

    public function transaction(Closure $callback): object
    {
        return $callback();
    }
}

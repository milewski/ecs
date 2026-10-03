<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Support;

/**
 * @method static ReflectionDatabaseConnection connection(string $name)
 * @method static object|null selectOne(string $query, array $bindings = [], bool $useReadPdo = true)
 * @method static object dispatch(callable(object $payload, array $metadata): object $callback, array $options = [])
 * @method static ReflectionDatabaseConnection|object ambiguous(string $name)
 */
final class ReflectionMagicConnectionFacade
{
    use ReflectionMagicQueryMethods;
}

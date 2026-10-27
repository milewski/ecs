<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Support;

final class ReflectionLivewireTestable
{
    private static array $macros = [];

    public static function macro(string $name, mixed $macro): void
    {
        self::$macros[ $name ] = $macro;
    }

    public static function flushMacros(): void
    {
        self::$macros = [];
    }
}

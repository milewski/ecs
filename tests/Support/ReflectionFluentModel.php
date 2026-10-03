<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Support;

/**
 * @mixin ReflectionFluentQuery
 */
final class ReflectionFluentModel
{
    public static function query(): self
    {
        return new self();
    }
}

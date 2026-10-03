<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Support;

final class ReflectionCastHolder
{
    public static ReflectionEnumCollectionCast $shared;

    public function __construct(
        public readonly ReflectionEnumCollectionCast $cast,
    )
    {
    }
}

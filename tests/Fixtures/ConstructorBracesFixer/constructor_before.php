<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\ConstructorBracesFixer;

final class EmptyBodyOnFollowingLineData
{
    public function __construct(
        public readonly string $text,
        public readonly string $type,
        public readonly int $typeId,
    ) {
    }
}

final class SingleLineEmptyBodyData
{
    public function __construct(
        public readonly string $text,
        public readonly string $type,
        public readonly int $typeId,
    ) {}
}

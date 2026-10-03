<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Support;

final class ReflectionEnumCollectionCast
{
    public function __construct(
        private readonly string $enumClass,
    )
    {
    }

    public static function make(string $enumClass): self
    {
        return new self($enumClass);
    }

    public function duplicate(): self
    {
        return new self($this->enumClass);
    }

    public function cast(object $property, mixed $value, array $properties, object $context): array
    {
        return [ $property, $value, $properties, $context, $this->enumClass ];
    }
}

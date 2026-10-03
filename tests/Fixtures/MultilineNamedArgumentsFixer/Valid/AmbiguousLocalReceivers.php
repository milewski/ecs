<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MultilineNamedArgumentsFixer;

final class AmbiguousLocalReceivers
{
    public function cast(object $property, mixed $value, array $properties, object $context): array
    {
        return [ $property, $value, $properties, $context ];
    }

    public function exercise(bool $condition, self|UnknownCast $union): void
    {
        $conditional = new self();

        if ($condition) {
            $conditional = unknown_cast();
        }

        $conditional->cast(
            (object) [],
            'conditional',
            [],
            (object) [],
        );

        $ternary = $condition ? new self() : unknown_cast();
        $ternary->cast(
            (object) [],
            'ternary',
            [],
            (object) [],
        );

        $unknown = new self();
        $unknown = unknown_cast();
        $unknown->cast(
            (object) [],
            'unknown-reassignment',
            [],
            (object) [],
        );

        $union->cast(
            (object) [],
            'union',
            [],
            (object) [],
        );
    }
}

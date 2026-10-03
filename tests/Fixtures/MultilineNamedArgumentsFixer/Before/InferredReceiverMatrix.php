<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MultilineNamedArgumentsFixer;

use Milewski\ECS\Tests\Support\ReflectionCastHolder;
use Milewski\ECS\Tests\Support\ReflectionEnumCollectionCast;

function make_fixture_cast(): ReflectionEnumCollectionCast
{
    return new ReflectionEnumCollectionCast(FixtureValueType::class);
}

final class InferredReceiverMatrix
{
    public function __invoke(string $first, string $second): array
    {
        return [ $first, $second ];
    }

    public function preserveOuterParameter(ReflectionEnumCollectionCast $reflectionEnumCollectionCast): void
    {
        $callback = function () use ($reflectionEnumCollectionCast): void {
            $reflectionEnumCollectionCast = unknown_cast();
        };

        $reflectionEnumCollectionCast->cast(
            (object) [],
            'outer-scope',
            [],
            (object) [],
        );
    }
}

test('statically inferred receiver forms use named arguments', function (): void {

    $property = (object) [];
    $properties = [];
    $context = (object) [];

    $direct = new ReflectionEnumCollectionCast(FixtureValueType::class);
    $direct->cast(
        $property,
        'direct',
        $properties,
        $context,
    );

    $holder = new ReflectionCastHolder($direct);
    $holder->cast->cast(
        $property,
        'nested-property',
        $properties,
        $context,
    );

    $propertyResult = $holder->cast;
    $propertyResult->cast(
        $property,
        'assigned-property',
        $properties,
        $context,
    );

    ReflectionCastHolder::$shared = $direct;
    ReflectionCastHolder::$shared->cast(
        $property,
        'static-property',
        $properties,
        $context,
    );

    $alias = $direct;
    $alias->cast(
        $property,
        'alias',
        $properties,
        $context,
    );

    $staticFactory = ReflectionEnumCollectionCast::make(FixtureValueType::class);
    $staticFactory->cast(
        $property,
        'static-factory',
        $properties,
        $context,
    );

    $instanceFactory = $direct->duplicate();
    $instanceFactory->cast(
        $property,
        'instance-factory',
        $properties,
        $context,
    );

    $functionResult = make_fixture_cast();
    $functionResult->cast(
        $property,
        'function-result',
        $properties,
        $context,
    );

    $grouped = (new ReflectionEnumCollectionCast(FixtureValueType::class));
    $grouped->cast(
        $property,
        'grouped',
        $properties,
        $context,
    );

    $cloned = clone $direct;
    $cloned->cast(
        $property,
        'cloned',
        $properties,
        $context,
    );

    $nullable = $direct;
    $nullable?->cast(
        $property,
        'nullsafe',
        $properties,
        $context,
    );

    $callable = new InferredReceiverMatrix();
    $callable(
        'first',
        'second',
    );

});

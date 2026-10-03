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
            property: (object) [],
            value: 'outer-scope',
            properties: [],
            context: (object) [],
        );
    }
}

test('statically inferred receiver forms use named arguments', function (): void {

    $property = (object) [];
    $properties = [];
    $context = (object) [];

    $direct = new ReflectionEnumCollectionCast(FixtureValueType::class);
    $direct->cast(
        property: $property,
        value: 'direct',
        properties: $properties,
        context: $context,
    );

    $holder = new ReflectionCastHolder($direct);
    $holder->cast->cast(
        property: $property,
        value: 'nested-property',
        properties: $properties,
        context: $context,
    );

    $propertyResult = $holder->cast;
    $propertyResult->cast(
        property: $property,
        value: 'assigned-property',
        properties: $properties,
        context: $context,
    );

    ReflectionCastHolder::$shared = $direct;

    ReflectionCastHolder::$shared->cast(
        property: $property,
        value: 'static-property',
        properties: $properties,
        context: $context,
    );

    $alias = $direct;
    $alias->cast(
        property: $property,
        value: 'alias',
        properties: $properties,
        context: $context,
    );

    $staticFactory = ReflectionEnumCollectionCast::make(FixtureValueType::class);
    $staticFactory->cast(
        property: $property,
        value: 'static-factory',
        properties: $properties,
        context: $context,
    );

    $instanceFactory = $direct->duplicate();
    $instanceFactory->cast(
        property: $property,
        value: 'instance-factory',
        properties: $properties,
        context: $context,
    );

    $functionResult = make_fixture_cast();
    $functionResult->cast(
        property: $property,
        value: 'function-result',
        properties: $properties,
        context: $context,
    );

    $grouped = (new ReflectionEnumCollectionCast(FixtureValueType::class));
    $grouped->cast(
        property: $property,
        value: 'grouped',
        properties: $properties,
        context: $context,
    );

    $cloned = clone $direct;
    $cloned->cast(
        property: $property,
        value: 'cloned',
        properties: $properties,
        context: $context,
    );

    $nullable = $direct;
    $nullable?->cast(
        property: $property,
        value: 'nullsafe',
        properties: $properties,
        context: $context,
    );

    $callable = new InferredReceiverMatrix();
    $callable(
        first: 'first',
        second: 'second',
    );

});

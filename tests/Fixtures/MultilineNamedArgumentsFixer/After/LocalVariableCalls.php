<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MultilineNamedArgumentsFixer;

use Milewski\ECS\Tests\Support\ReflectionEnumCollectionCast;

use function Pest\Faker\fake;

test('local values retain their inferred object type', function (): void {

    $cast = new ReflectionEnumCollectionCast(FixtureValueType::class);
    $result = $cast->cast(
        property: fake(),
        value: 'not-an-array',
        properties: [],
        context: fake(),
    );

    expect($result)->toBeArray();

});

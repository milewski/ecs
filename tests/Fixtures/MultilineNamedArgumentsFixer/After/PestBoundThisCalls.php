<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MultilineNamedArgumentsFixer;

use LogicException;
use Milewski\ECS\Tests\Support\ReflectionPestAssertions;
use Milewski\ECS\Tests\Support\ReflectionPestTestCase;

final class Experiment
{
}

final class LandingGroupResultData
{
    public static function summary(Experiment $experiment, Experiment $definition): void
    {
    }
}

uses(ReflectionPestTestCase::class, ReflectionPestAssertions::class);

test('Pest binds the configured test case and traits to the closure', function (): void {

    $invalidDefinition = new Experiment();

    $this->assertThrows(
        test: fn () => LandingGroupResultData::summary(new Experiment(), $invalidDefinition),
        expectedClass: LogicException::class,
        expectedMessage: 'Only a configured landing group',
    );

    $this->assertResourceAccess(
        resource: new Experiment(),
        ability: 'read',
    );

});

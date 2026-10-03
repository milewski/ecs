<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MultilineNamedArgumentsFixer;

use Milewski\ECS\Tests\Support\ReflectionPestAssertions;
use Milewski\ECS\Tests\Support\ReflectionPestTestCase;
use LogicException;

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
        fn () => LandingGroupResultData::summary(new Experiment(), $invalidDefinition),
        LogicException::class,
        'Only a configured landing group',
    );

    $this->assertResourceAccess(
        new Experiment(),
        'read',
    );
});

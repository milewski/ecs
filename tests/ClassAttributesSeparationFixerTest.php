<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests;

use Milewski\ECS\Tests\Support\EcsTestCase;

final class ClassAttributesSeparationFixerTest extends EcsTestCase
{
    public function test_class_methods_are_separated_by_exactly_one_blank_line(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/ClassAttributesSeparationFixer';

        $this->assertFixtureIsFixedTo(
            $fixtureDirectory . '/Before/ApplicationServiceProvider.php',
            $fixtureDirectory . '/After/ApplicationServiceProvider.php',
        );
    }
}

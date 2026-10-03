<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests;

use Milewski\ECS\Tests\Support\EcsTestCase;

final class ConstructorBracesFixerTest extends EcsTestCase
{
    public function test_constructor_braces_are_always_placed_on_separate_lines(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/ConstructorBracesFixer';

        $this->assertFixtureIsFixedTo(
            $fixtureDirectory . '/constructor_before.php',
            $fixtureDirectory . '/constructor_after.php',
        );
    }
}

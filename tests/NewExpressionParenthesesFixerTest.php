<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests;

use Milewski\ECS\Tests\Support\EcsTestCase;

final class NewExpressionParenthesesFixerTest extends EcsTestCase
{
    public function test_parentheses_are_removed_from_chained_new_expressions(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/NewExpressionParenthesesFixer';

        $this->assertFixtureIsFixedTo(
            $fixtureDirectory . '/Before/Example.php',
            $fixtureDirectory . '/After/Example.php',
        );
    }
}

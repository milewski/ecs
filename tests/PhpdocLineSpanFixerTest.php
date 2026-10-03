<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests;

use Milewski\ECS\Tests\Support\EcsTestCase;

final class PhpdocLineSpanFixerTest extends EcsTestCase
{
    public function test_inline_variable_docblock_is_expanded_to_multiple_lines(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/PhpdocLineSpanFixer';

        $this->assertFixtureIsFixedTo(
            $fixtureDirectory . '/Before/ExperimentLookup.php',
            $fixtureDirectory . '/After/ExperimentLookup.php',
        );
    }
}

<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests;

use Milewski\ECS\Tests\Support\EcsTestCase;

final class PaddedDocblockFixerTest extends EcsTestCase
{
    public function test_docblocks_following_statements_have_a_blank_line_before_them(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/PaddedDocblockFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/AdjacentDocblocks.php',
            expectedFixture: $fixtureDirectory . '/After/AdjacentDocblocks.php',
        );
    }

    public function test_padded_docblocks_are_idempotent(): void
    {
        $this->assertFixturePasses(
            __DIR__ . '/Fixtures/PaddedDocblockFixer/After/AdjacentDocblocks.php',
        );
    }
}

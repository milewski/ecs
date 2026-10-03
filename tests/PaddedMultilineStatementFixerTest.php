<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests;

use Milewski\ECS\Tests\Support\EcsTestCase;

final class PaddedMultilineStatementFixerTest extends EcsTestCase
{
    public function test_multiline_statements_are_followed_by_a_blank_line(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/PaddedMultilineStatementFixer';

        $this->assertFixtureIsFixedTo(
            $fixtureDirectory . '/Before/MultilineStatements.php',
            $fixtureDirectory . '/After/MultilineStatements.php',
        );
    }

    public function test_array_offset_traversal_does_not_crash(): void
    {
        $this->assertFixturePasses(
            __DIR__ . '/Fixtures/PaddedMultilineStatementFixer/Valid/CampaignSlideshowResource.php',
        );
    }
}

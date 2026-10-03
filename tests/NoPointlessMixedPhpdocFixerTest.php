<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests;

use Milewski\ECS\Tests\Support\EcsTestCase;

final class NoPointlessMixedPhpdocFixerTest extends EcsTestCase
{
    public function test_pointless_mixed_annotations_are_removed(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/NoPointlessMixedPhpdocFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/PointlessMixedPhpdocs.php',
            expectedFixture: $fixtureDirectory . '/After/PointlessMixedPhpdocs.php',
        );
    }

    public function test_cleaned_docblocks_are_idempotent(): void
    {
        $this->assertFixturePasses(
            __DIR__ . '/Fixtures/NoPointlessMixedPhpdocFixer/After/PointlessMixedPhpdocs.php',
        );
    }
}

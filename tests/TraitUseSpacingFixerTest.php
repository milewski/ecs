<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests;

use Milewski\ECS\Tests\Support\EcsTestCase;

final class TraitUseSpacingFixerTest extends EcsTestCase
{
    public function test_single_trait_is_separated_from_following_method(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/TraitUseSpacingFixer';

        $this->assertFixtureIsFixedTo(
            $fixtureDirectory . '/Before/SingleTraitController.php',
            $fixtureDirectory . '/After/SingleTraitController.php',
        );
    }

    public function test_multiple_traits_are_grouped_before_following_method(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/TraitUseSpacingFixer';

        $this->assertFixtureIsFixedTo(
            $fixtureDirectory . '/Before/MultipleTraitsController.php',
            $fixtureDirectory . '/After/MultipleTraitsController.php',
        );
    }

    public function test_trait_is_separated_from_following_property(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/TraitUseSpacingFixer';

        $this->assertFixtureIsFixedTo(
            $fixtureDirectory . '/Before/TraitBeforeProperty.php',
            $fixtureDirectory . '/After/TraitBeforeProperty.php',
        );
    }
}

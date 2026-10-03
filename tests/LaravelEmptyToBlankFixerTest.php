<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests;

use Milewski\ECS\Tests\Support\EcsTestCase;

final class LaravelEmptyToBlankFixerTest extends EcsTestCase
{
    public function test_empty_checks_use_laravel_helpers_in_laravel_projects(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/LaravelEmptyToBlankFixer';

        $this->assertLaravelFixtureIsFixedTo(
            $fixtureDirectory . '/Before/EmptyChecks.php',
            $fixtureDirectory . '/After/EmptyChecks.php',
        );
    }

    public function test_empty_checks_remain_unchanged_outside_laravel_projects(): void
    {
        $this->assertFixturePasses(
            __DIR__ . '/Fixtures/LaravelEmptyToBlankFixer/Before/EmptyChecks.php',
        );
    }
}

<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests;

use Milewski\ECS\Tests\Support\EcsTestCase;

final class AutoImportClassesFixerTest extends EcsTestCase
{
    public function test_fully_qualified_class_references_are_imported(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/AutoImportClassesFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/ClassReferences.php',
            expectedFixture: $fixtureDirectory . '/After/ClassReferences.php',
        );
    }

    public function test_conflicting_short_names_receive_deterministic_aliases(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/AutoImportClassesFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/ConflictingClasses.php',
            expectedFixture: $fixtureDirectory . '/After/ConflictingClasses.php',
        );
    }

    public function test_generated_aliases_do_not_conflict_with_existing_imports(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/AutoImportClassesFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/AliasCollision.php',
            expectedFixture: $fixtureDirectory . '/After/AliasCollision.php',
        );
    }

    public function test_conflicts_are_resolved_independently_in_each_namespace(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/AutoImportClassesFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/MultipleNamespaces.php',
            expectedFixture: $fixtureDirectory . '/After/MultipleNamespaces.php',
        );
    }

    public function test_fully_qualified_functions_and_constants_are_unchanged(): void
    {
        $this->assertFixturePasses(
            __DIR__ . '/Fixtures/AutoImportClassesFixer/Valid/FunctionsAndConstants.php',
        );
    }
}

<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests;

use Milewski\ECS\Tests\Support\EcsTestCase;

final class ClassOpeningBracketFixerTest extends EcsTestCase
{
    public function test_anonymous_class_after_a_named_class_uses_a_new_line_for_its_opening_brace(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/ClassOpeningBracketFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/AnonymousOperation.php',
            expectedFixture: $fixtureDirectory . '/After/AnonymousOperation.php',
        );
    }

    public function test_anonymous_class_brace_output_is_idempotent(): void
    {
        $this->assertFixturePasses(
            __DIR__ . '/Fixtures/ClassOpeningBracketFixer/After/AnonymousOperation.php',
        );
    }
}

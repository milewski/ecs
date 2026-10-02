<?php

declare(strict_types = 1);

namespace DigitalCreative\ECS\Tests;

use DigitalCreative\ECS\Tests\Support\EcsTestCase;

final class FunctionParameterLayoutFixerTest extends EcsTestCase
{
    public function test_the_default_preset_expands_a_single_statement_method_body(): void
    {
        $this->assertFixtureIsFixedTo(
            inputFixture: __DIR__ . '/Fixtures/FunctionParameterLayout/Before/TicketOperations.php',
            expectedFixture: __DIR__ . '/Fixtures/FunctionParameterLayout/After/TicketOperations.php',
        );
    }

    public function test_the_default_preset_preserves_expanded_method_bodies(): void
    {
        $this->assertFixturePasses(
            fixture: __DIR__ . '/Fixtures/FunctionParameterLayout/After/TicketOperations.php',
        );
    }
}

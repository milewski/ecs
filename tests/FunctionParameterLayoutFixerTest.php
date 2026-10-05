<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests;

use Milewski\ECS\Tests\Support\EcsTestCase;

final class FunctionParameterLayoutFixerTest extends EcsTestCase
{
    public function test_the_default_preset_places_constructor_parameter_attributes_on_separate_lines(): void
    {
        $this->assertFixtureIsFixedTo(
            inputFixture: __DIR__ . '/Fixtures/FunctionParameterLayout/Before/CrmLoginData.php',
            expectedFixture: __DIR__ . '/Fixtures/FunctionParameterLayout/After/CrmLoginData.php',
        );
    }

    public function test_the_default_preset_preserves_constructor_parameter_attribute_lines(): void
    {
        $this->assertFixturePasses(
            fixture: __DIR__ . '/Fixtures/FunctionParameterLayout/After/CrmLoginData.php',
        );
    }

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

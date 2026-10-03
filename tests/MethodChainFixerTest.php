<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests;

use Milewski\ECS\Tests\Support\EcsTestCase;

final class MethodChainFixerTest extends EcsTestCase
{
    public function test_long_chains_and_constructors_in_typed_arrow_callbacks_are_expanded(): void
    {
        $this->assertFixtureIsFixedTo(
            inputFixture: __DIR__ . '/Fixtures/MethodChainFixer/Before/TicketPresentation.php',
            expectedFixture: __DIR__ . '/Fixtures/MethodChainFixer/After/TicketPresentation.php',
        );
    }

    public function test_ticket_presentation_output_is_idempotent(): void
    {
        $this->assertFixturePasses(__DIR__ . '/Fixtures/MethodChainFixer/After/TicketPresentation.php');
    }

    public function test_long_static_factory_chains_expand_and_short_constructor_calls_stay_compact(): void
    {
        $this->assertFixtureIsFixedTo(
            inputFixture: __DIR__ . '/Fixtures/MethodChainFixer/Before/RolePolicies.php',
            expectedFixture: __DIR__ . '/Fixtures/MethodChainFixer/After/RolePolicies.php',
        );
    }

    public function test_role_policy_output_is_idempotent(): void
    {
        $this->assertFixturePasses(__DIR__ . '/Fixtures/MethodChainFixer/After/RolePolicies.php');
    }
}

<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests;

use Milewski\ECS\Tests\Support\EcsTestCase;

final class MethodChainFixerTest extends EcsTestCase
{
    public function test_request_chains_keep_the_first_method_call_with_the_receiver(): void
    {
        $this->assertFixtureIsFixedTo(
            inputFixture: __DIR__ . '/Fixtures/MethodChainFixer/Before/RecoveryRequests.php',
            expectedFixture: __DIR__ . '/Fixtures/MethodChainFixer/After/RecoveryRequests.php',
        );
    }

    public function test_request_chain_layout_is_idempotent(): void
    {
        $this->assertFixturePasses(__DIR__ . '/Fixtures/MethodChainFixer/After/RecoveryRequests.php');
    }

    public function test_joining_the_first_call_reindents_its_multiline_arguments_in_one_pass(): void
    {
        $input = <<<'PHP'
        <?php

        declare(strict_types = 1);

        final class RecoveryRequests
        {
            public function verify(string $plaintext): void
            {
                $this
                    ->getJson(
                        route('recovery.show', [ 'token' => $plaintext ]),
                        [ 'Accept' => 'application/json' ],
                    )
                    ->assertExactJson([ 'status' => 'unavailable' ]);
            }
        }

        PHP;

        $expected = <<<'PHP'
        <?php

        declare(strict_types = 1);

        final class RecoveryRequests
        {
            public function verify(string $plaintext): void
            {
                $this->getJson(
                    route('recovery.show', [ 'token' => $plaintext ]),
                    [ 'Accept' => 'application/json' ],
                )
                    ->assertExactJson([ 'status' => 'unavailable' ]);
            }
        }

        PHP;

        $this->assertCodeIsFixedTo($input, $expected, 'RecoveryRequests.php');
        $this->assertCodeIsFixedTo($expected, $expected, 'RecoveryRequests.php');
    }

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

    public function test_conditions_are_preserved_and_membership_chains_wrap_before_arguments(): void
    {
        $this->assertFixtureIsFixedTo(
            inputFixture: __DIR__ . '/Fixtures/MethodChainFixer/Before/InlineConditions.php',
            expectedFixture: __DIR__ . '/Fixtures/MethodChainFixer/After/InlineConditions.php',
        );
    }

    public function test_condition_and_membership_output_is_idempotent(): void
    {
        $this->assertFixturePasses(__DIR__ . '/Fixtures/MethodChainFixer/After/InlineConditions.php');
    }

    public function test_long_constructor_arguments_wrap_without_expanding_short_nested_chains(): void
    {
        $this->assertFixtureIsFixedTo(
            inputFixture: __DIR__ . '/Fixtures/MethodChainFixer/Before/CustomerNotePages.php',
            expectedFixture: __DIR__ . '/Fixtures/MethodChainFixer/After/CustomerNotePages.php',
        );
    }

    public function test_customer_note_page_output_is_idempotent(): void
    {
        $this->assertFixturePasses(__DIR__ . '/Fixtures/MethodChainFixer/After/CustomerNotePages.php');
    }
}

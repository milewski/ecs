<?php

declare(strict_types = 1);

namespace DigitalCreative\ECS\Tests;

use DigitalCreative\ECS\Tests\Support\EcsTestCase;

final class DescriptiveVariableNameFixerTest extends EcsTestCase
{
    public function test_call_argument_names_use_the_renamed_parameter(): void
    {
        $input = <<<'PHP'
        <?php

        declare(strict_types = 1);

        final class ArgumentNames
        {
            public function call(Ticket $ticket): TicketData
            {
                return $this->present(
                    $ticket,
                    false,
                );
            }

            private function present(Ticket $t, bool $archived): TicketData
            {
                return new TicketData($t, $archived);
            }
        }

        PHP;

        $expected = str_replace(
            search: [ 'Ticket $t,', 'TicketData($t,', "            \$ticket,\n            false," ],
            replace: [ 'Ticket $ticket,', 'TicketData($ticket,', "            ticket: \$ticket,\n            archived: false," ],
            subject: $input,
        );

        $this->assertCodeIsFixedTo($input, $expected, 'ArgumentNames.php');
    }

    public function test_renamed_callback_output_is_idempotent(): void
    {
        $this->assertFixturePasses(__DIR__ . '/Fixtures/DescriptiveVariableNameFixer/After/ArrowFunctions.php');
    }

    public function test_single_letters_and_type_related_abbreviations_are_replaced(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/DescriptiveVariableNameFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/ArrowFunctions.php',
            expectedFixture: $fixtureDirectory . '/After/ArrowFunctions.php',
        );
    }

    public function test_references_are_renamed_without_crossing_nested_scope_boundaries(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/DescriptiveVariableNameFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/ClosureScopes.php',
            expectedFixture: $fixtureDirectory . '/After/ClosureScopes.php',
        );
    }

    public function test_names_are_inferred_from_nullable_union_builtin_and_qualified_types(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/DescriptiveVariableNameFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/TypeInference.php',
            expectedFixture: $fixtureDirectory . '/After/TypeInference.php',
        );
    }

    public function test_ambiguous_or_unsupported_variables_remain_unchanged(): void
    {
        $this->assertFixturePasses(
            __DIR__ . '/Fixtures/DescriptiveVariableNameFixer/Valid/UncertainVariables.php',
        );
    }
}

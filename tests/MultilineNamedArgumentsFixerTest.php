<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests;

use Milewski\ECS\Tests\Support\EcsTestCase;

final class MultilineNamedArgumentsFixerTest extends EcsTestCase
{
    public function test_newly_expanded_calls_receive_control_block_padding_in_one_pass(): void
    {
        $input = <<<'PHP'
        <?php

        declare(strict_types = 1);

        final class ConditionalCalls
        {
            public function send(bool $ready, object $ticket, object $customer): void
            {
                if ($ready) {
                    unknown_function($ticket->recipientDisplayNameForTheTicketNotification, $customer->recipientDisplayNameForTheTicketNotification);
                }
            }
        }

        PHP;

        $expected = <<<'PHP'
        <?php

        declare(strict_types = 1);

        final class ConditionalCalls
        {
            public function send(bool $ready, object $ticket, object $customer): void
            {
                if ($ready) {

                    unknown_function(
                        $ticket->recipientDisplayNameForTheTicketNotification,
                        $customer->recipientDisplayNameForTheTicketNotification,
                    );

                }
            }
        }

        PHP;

        $this->assertCodeIsFixedTo($input, $expected, 'ConditionalCalls.php');
        $this->assertCodeIsFixedTo($expected, $expected, 'ConditionalCalls.php');
    }

    public function test_long_constructor_calls_are_expanded_and_named(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/LongCalls.php',
            expectedFixture: $fixtureDirectory . '/After/LongCalls.php',
        );
    }

    public function test_expanded_long_calls_are_idempotent(): void
    {
        $this->assertFixturePasses(
            fixture: __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer/After/LongCalls.php',
        );
    }

    public function test_multiline_calls_use_resolved_parameter_names(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/MultilineCalls.php',
            expectedFixture: $fixtureDirectory . '/After/MultilineCalls.php',
        );
    }

    public function test_methods_called_on_new_objects_use_resolved_parameter_names(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/PestValidation.php',
            expectedFixture: $fixtureDirectory . '/After/PestValidation.php',
        );
    }

    public function test_fluent_method_chains_use_resolved_parameter_names(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/FluentQuery.php',
            expectedFixture: $fixtureDirectory . '/After/FluentQuery.php',
        );
    }

    public function test_typed_property_receivers_use_resolved_parameter_names(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/TypedPropertyCalls.php',
            expectedFixture: $fixtureDirectory . '/After/TypedPropertyCalls.php',
        );
    }

    public function test_typed_parameter_receivers_use_resolved_parameter_names(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/ParameterReceiverCalls.php',
            expectedFixture: $fixtureDirectory . '/After/ParameterReceiverCalls.php',
        );
    }

    public function test_locally_assigned_object_receivers_use_resolved_parameter_names(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/LocalVariableCalls.php',
            expectedFixture: $fixtureDirectory . '/After/LocalVariableCalls.php',
        );
    }

    public function test_local_type_inference_covers_aliases_factories_clones_and_invokable_objects(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/InferredReceiverMatrix.php',
            expectedFixture: $fixtureDirectory . '/After/InferredReceiverMatrix.php',
        );
    }

    public function test_namespace_alias_and_source_inheritance_are_resolved(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/NamespaceResolution.php',
            expectedFixture: $fixtureDirectory . '/After/NamespaceResolution.php',
        );
    }

    public function test_class_string_function_receivers_use_resolved_parameter_names(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/ClassStringFunctionCalls.php',
            expectedFixture: $fixtureDirectory . '/After/ClassStringFunctionCalls.php',
        );
    }

    public function test_pest_bound_this_uses_configured_test_case_and_trait_parameter_names(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/PestBoundThisCalls.php',
            expectedFixture: $fixtureDirectory . '/After/PestBoundThisCalls.php',
        );
    }

    public function test_pest_bound_this_output_is_idempotent(): void
    {
        $this->assertFixturePasses(
            fixture: __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer/After/PestBoundThisCalls.php',
        );
    }

    public function test_magic_methods_functions_traits_top_level_calls_and_complex_arguments_are_resolved(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/ReceiverContextMatrix.php',
            expectedFixture: $fixtureDirectory . '/After/ReceiverContextMatrix.php',
        );
    }

    public function test_captured_receivers_are_resolved_through_nested_test_and_arrow_function_scopes(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/CapturedClosureCalls.php',
            expectedFixture: $fixtureDirectory . '/After/CapturedClosureCalls.php',
        );
    }

    public function test_typed_arrow_parameters_resolve_nested_method_arguments(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/NestedArrowRuleCalls.php',
            expectedFixture: $fixtureDirectory . '/After/NestedArrowRuleCalls.php',
        );
    }

    public function test_fluent_when_callbacks_use_resolved_parameter_names(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/ConditionalQueryCalls.php',
            expectedFixture: $fixtureDirectory . '/After/ConditionalQueryCalls.php',
        );
    }

    public function test_nested_callback_outputs_are_idempotent(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer/After';

        $this->assertFixturePasses($fixtureDirectory . '/NestedArrowRuleCalls.php');
        $this->assertFixturePasses($fixtureDirectory . '/ConditionalQueryCalls.php');
    }

    public function test_comprehensive_receiver_context_output_is_idempotent(): void
    {
        $this->assertFixturePasses(
            fixture: __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer/After/ReceiverContextMatrix.php',
        );
    }

    public function test_captured_receiver_output_is_idempotent(): void
    {
        $this->assertFixturePasses(
            fixture: __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer/After/CapturedClosureCalls.php',
        );
    }

    public function test_class_string_function_output_is_idempotent(): void
    {
        $this->assertFixturePasses(
            fixture: __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer/After/ClassStringFunctionCalls.php',
        );
    }

    public function test_already_named_multiline_calls_are_idempotent(): void
    {
        $this->assertFixturePasses(
            fixture: __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer/After/MultilineCalls.php',
        );
    }

    public function test_inferred_receiver_output_is_idempotent(): void
    {
        $this->assertFixturePasses(
            fixture: __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer/After/InferredReceiverMatrix.php',
        );
    }

    public function test_inline_first_and_single_argument_calls_are_left_unchanged(): void
    {
        $this->assertFixturePasses(
            fixture: __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer/Valid/PartiallyMultilineCalls.php',
        );
    }

    public function test_unresolvable_variadic_and_unpacked_calls_are_left_unchanged(): void
    {
        $this->assertFixturePasses(
            fixture: __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer/Valid/UnresolvableCalls.php',
        );
    }

    public function test_ambiguous_conditional_union_and_unknown_assignments_are_left_unchanged(): void
    {
        $this->assertFixturePasses(
            fixture: __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer/Valid/AmbiguousLocalReceivers.php',
        );
    }

    public function test_ambiguous_top_level_magic_and_captured_receivers_are_left_unchanged(): void
    {
        $this->assertFixturePasses(
            fixture: __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer/Valid/AmbiguousScopeAndMagicCalls.php',
        );
    }

    public function test_ambiguous_and_dynamic_class_string_functions_are_left_unchanged(): void
    {
        $this->assertFixturePasses(
            fixture: __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer/Valid/AmbiguousClassStringFunctionCalls.php',
        );
    }

    public function test_class_string_in_static_and_regular_arrow_functions_resolves_names(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/ClassStringFunctionCallsInArrowFunctions.php',
            expectedFixture: $fixtureDirectory . '/After/ClassStringFunctionCallsInArrowFunctions.php',
        );
    }

    public function test_class_string_function_with_complex_return_type_resolves_names(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/MultilineNamedArgumentsFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/ComplexReturnTypeClassStringCalls.php',
            expectedFixture: $fixtureDirectory . '/After/ComplexReturnTypeClassStringCalls.php',
        );
    }
}

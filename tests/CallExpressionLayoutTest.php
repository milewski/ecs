<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests;

use Milewski\ECS\Tests\Support\EcsTestCase;

final class CallExpressionLayoutTest extends EcsTestCase
{
    public function test_grouped_billing_arguments_and_arrays_of_multiline_objects_are_expanded(): void
    {
        $this->assertFixtureIsFixedTo(
            inputFixture: __DIR__ . '/Fixtures/CallExpressionLayout/Before/BillingScenarios.php',
            expectedFixture: __DIR__ . '/Fixtures/CallExpressionLayout/After/BillingScenarios.php',
        );
    }

    public function test_billing_layout_is_idempotent(): void
    {
        $this->assertFixturePasses(__DIR__ . '/Fixtures/CallExpressionLayout/After/BillingScenarios.php');
    }

    public function test_sprintf_moves_onto_its_parent_argument_line(): void
    {
        $this->assertFixtureIsFixedTo(
            inputFixture: __DIR__ . '/Fixtures/CallExpressionLayout/Before/RateLimit.php',
            expectedFixture: __DIR__ . '/Fixtures/CallExpressionLayout/After/RateLimit.php',
        );
    }

    public function test_rate_limiter_output_is_idempotent(): void
    {
        $this->assertFixturePasses(__DIR__ . '/Fixtures/CallExpressionLayout/After/RateLimit.php');
    }

    public function test_query_receiver_type_survives_when_and_if_else_branches(): void
    {
        $this->assertFixtureIsFixedTo(
            inputFixture: __DIR__ . '/Fixtures/CallExpressionLayout/Before/CustomerSearch.php',
            expectedFixture: __DIR__ . '/Fixtures/CallExpressionLayout/After/CustomerSearch.php',
        );
    }

    public function test_customer_search_output_is_idempotent(): void
    {
        $this->assertFixturePasses(__DIR__ . '/Fixtures/CallExpressionLayout/After/CustomerSearch.php');
    }

    public function test_boolean_arguments_stay_inline_in_expanded_outer_calls(): void
    {
        $input = <<<'PHP'
        <?php

        declare(strict_types = 1);

        function abort_unless(bool $boolean, int $code, string $message): void
        {
        }

        abort_unless(in_array('crm.customers.browse', $context->permissions, true) || in_array($leadId, $this->disclosedLeadIds($user), true), 404, 'Customer not found.');

        PHP;

        $expected = <<<'PHP'
        <?php

        declare(strict_types = 1);

        function abort_unless(bool $boolean, int $code, string $message): void
        {
        }

        abort_unless(
            boolean: in_array('crm.customers.browse', $context->permissions, true) || in_array($leadId, $this->disclosedLeadIds($user), true),
            code: 404,
            message: 'Customer not found.',
        );

        PHP;

        $this->assertCodeIsFixedTo($input, $expected);
        $this->assertCodeIsFixedTo($expected, $expected);
    }
}

<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests;

use Milewski\ECS\Tests\Support\EcsTestCase;

final class PaddedArrayFixerTest extends EcsTestCase
{
    public function test_billing_plan_arrays_expand_multiline_elements(): void
    {
        $this->assertFixtureIsFixedTo(
            inputFixture: __DIR__ . '/Fixtures/PaddedArrayFixer/Before/BillingSnapshot.php',
            expectedFixture: __DIR__ . '/Fixtures/PaddedArrayFixer/After/BillingSnapshot.php',
        );
    }

    public function test_billing_snapshot_output_is_idempotent(): void
    {
        $this->assertFixturePasses(__DIR__ . '/Fixtures/PaddedArrayFixer/After/BillingSnapshot.php');
    }

    public function test_arrays_expand_after_long_constructor_arguments_are_wrapped(): void
    {
        $input = <<<'PHP'
        <?php

        declare(strict_types = 1);

        $plans = [ new BillingPlanData(priceId: 'local-monthly', name: 'Local monthly care scenario', amountCents: 29900, currency: 'usd', intervalDays: 28) ];

        PHP;

        $expected = <<<'PHP'
        <?php

        declare(strict_types = 1);

        $plans = [
            new BillingPlanData(
                priceId: 'local-monthly',
                name: 'Local monthly care scenario',
                amountCents: 29900,
                currency: 'usd',
                intervalDays: 28,
            ),
        ];

        PHP;

        $this->assertCodeIsFixedTo($input, $expected);
        $this->assertCodeIsFixedTo($expected, $expected);
    }

    public function test_nested_arrays_expand_without_changing_compact_siblings(): void
    {
        $input = <<<'PHP'
        <?php

        declare(strict_types = 1);

        $groups = [ 'plans' => [new BillingPlanData(
            amountCents: 29900,
            currency: 'usd',
        )], 'intervals' => [28, 84] ];

        PHP;

        $expected = <<<'PHP'
        <?php

        declare(strict_types = 1);

        $groups = [
            'plans' => [
                new BillingPlanData(
                    amountCents: 29900,
                    currency: 'usd',
                ),
            ],
            'intervals' => [ 28, 84 ],
        ];

        PHP;

        $this->assertCodeIsFixedTo($input, $expected);
        $this->assertCodeIsFixedTo($expected, $expected);
    }
}

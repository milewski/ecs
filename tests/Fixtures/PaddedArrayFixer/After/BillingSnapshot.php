<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\PaddedArrayFixer;

final class BillingSnapshot
{
    private function snapshot(string $subscriptionId): BillingSnapshotData
    {
        return new BillingSnapshotData(
            subscriptionId: $subscriptionId,
            plans: [
                new BillingPlanData(
                    priceId: 'local-monthly',
                    name: 'Local monthly care scenario',
                    amountCents: 29900,
                    currency: 'usd',
                    intervalDays: 28,
                ),
                new BillingPlanData(
                    priceId: 'local-quarterly',
                    name: 'Local quarterly care scenario',
                    amountCents: 74700,
                    currency: 'usd',
                    intervalDays: 84,
                ),
            ],
            mode: 'captured',
        );
    }

    private function singlePlan(): array
    {
        return [
            new BillingPlanData(
                priceId: 'local-quarterly',
                name: 'Local quarterly care scenario',
                amountCents: 74700,
                currency: 'usd',
                intervalDays: 84,
            ),
        ];
    }
}

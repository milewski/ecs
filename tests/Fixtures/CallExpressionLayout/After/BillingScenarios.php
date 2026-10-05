<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\CallExpressionLayout;

final class BillingScenarios
{
    public function capture(object $lead, object $subscription, bool $pastDue): void
    {
        $this->billingStateRepository->save($lead->id, new BillingSnapshotData(
            subscriptionId: $subscription->id,
            plan: 'Local monthly care scenario',
            status: $pastDue ? 'past_due' : 'active',
            priceCents: 29900,
            currency: 'usd',
            nextChargeAt: now()->addDays(28)->toISOString(),
            card: 'Local test card 4242',
            cancelAtEnd: false,
            paused: false,
            invoiceId: $subscription->stripe_invoice_id,
            invoiceAmountCents: $pastDue ? 29900 : 0,
            retryAllowed: $pastDue,
            charges: [
                new BillingChargeData(
                    id: sprintf('local-charge-%s', $lead->id),
                    amountCents: 59800,
                    refundedCents: 0,
                    currency: 'usd',
                    paidAt: now()->subDays(28)->toISOString(),
                    disputed: $lead->first_name === 'Blair',
                    remainingCents: 59800,
                ),
            ],
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
            revision: '',
            mode: 'captured',
            lifetimeValueCents: 59800,
        ));
    }
}

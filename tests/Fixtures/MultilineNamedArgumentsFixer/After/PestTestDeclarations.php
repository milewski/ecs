<?php

declare(strict_types = 1);

test('verification cannot turn an uncertain or still-payable intent into a failed purchase', function (string $status) use ($actingAsLead, $mockStripeWithStatus): void {

    $lead = $actingAsLead($this);
    $subscription = SubscriptionFactory::new()->create([
        'lead_id' => $lead->id,
        'stripe_subscription_id' => 'sub_test123',
    ]);

    $mockStripeWithStatus($status);

    $this->postJson(route('subscriptions.verify'), [ 'subscription_attempt_id' => $subscription->id ])
        ->assertOk()
        ->assertJsonPath('status', 'pending');

    $this->assertSame(SubscriptionStatus::Pending, $subscription->fresh()->status);

})->with([ 'unknown', 'requires_payment_method', 'requires_confirmation', 'requires_capture' ]);

test('a completed payment cannot be downgraded by a later canceled intent', function () use ($actingAsLead, $mockStripeWithStatus): void {

    $lead = $actingAsLead($this);
    $subscription = SubscriptionFactory::new()->completed()->create([
        'lead_id' => $lead->id,
        'stripe_subscription_id' => 'sub_test123',
    ]);

    $mockStripeWithStatus('canceled');

    $this->postJson(route('subscriptions.verify'), [ 'subscription_attempt_id' => $subscription->id ])
        ->assertOk()
        ->assertJsonPath('status', 'pending');

    $this->assertSame(SubscriptionStatus::Completed, $subscription->fresh()->status);
    $this->assertTrue($subscription->completed_at->equalTo($subscription->fresh()->completed_at));

});

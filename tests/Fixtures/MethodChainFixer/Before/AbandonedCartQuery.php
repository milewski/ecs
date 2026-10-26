<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MethodChainFixer;

final class AbandonedCartQuery
{
    private function abandonedCartEligibilityQuery(CarbonInterface $cutoff): Builder
    {
        $closedCutoff = now()->subMinutes((int) config('openloop.abandoned_cart_closed_delay_minutes'));

        return Form::query()
            ->where('form_provider', FormProvider::OpenLoop->value)
            ->where(static function (Builder $abandoned) use ($cutoff, $closedCutoff): void {

                $abandoned
                    ->where(static function (Builder $inactive) use ($cutoff): void {

                        $inactive
                            ->whereIn(
                                column: 'id',
                                values: app(OpenLoopHealthieStateRepository::class)->submittedFormIdsQuery($cutoff, FormProvider::OpenLoop),
                            )
                            ->where('last_visitor_activity_at', '<=', $cutoff);

                    })
                    ->orWhere(static function (Builder $closed) use ($closedCutoff): void {

                        $closed
                            ->whereIn(
                                column: 'id',
                                values: app(OpenLoopHealthieStateRepository::class)->submittedFormIdsQuery(provider: FormProvider::OpenLoop),
                            )
                            ->where('checkout_closed_at', '<=', $closedCutoff)
                            ->whereColumn('checkout_closed_at', '>=', 'last_visitor_activity_at');

                    });

            })
            ->whereNull('abandoned_cart_sent_at')

            ->whereNotIn('id', app(SubscriptionRepository::class)->completedAbandonedCartFormIdsQuery());
    }
}

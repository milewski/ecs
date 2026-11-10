<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MethodChainFixer;

final class AffiliateQueries
{
    public function mcpAffiliateCodeCounts(string $affiliateId): Collection
    {
        return Customer::query()->where('affiliate_id', $affiliateId)->whereNotNull('affiliate_discount_code_id')
            ->select('affiliate_discount_code_id')->selectRaw('count(*) as customers_count')->groupBy('affiliate_discount_code_id')
            ->toBase()->get()->mapWithKeys(static fn (object $row): array => [ $row->affiliate_discount_code_id => (int) $row->customers_count ]);
    }

    public function velocityCountFor(string $affiliateId, CarbonImmutable $now): AffiliateVelocityCount
    {
        $row = $this->velocityWindow($now)->where('affiliate_id', $affiliateId)
            ->selectRaw('count(*) filter (where created_at > ?) as hour_count', [ $now->subHour() ])
            ->selectRaw('count(*) as day_count')->toBase()->first();

        return new AffiliateVelocityCount($affiliateId, (int) $row->hour_count, (int) $row->day_count);
    }
}

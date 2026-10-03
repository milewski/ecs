<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\CallExpressionLayout;

use Milewski\ECS\Tests\Support\ReflectionConditionalModel as Lead;
use Milewski\ECS\Tests\Support\ReflectionConditionalQuery as Builder;

final class CustomerSearch
{
    public function crmSearch(CustomerSearchData $search, ?array $knownLeadIds = null): Collection
    {
        $identity = $search->normalizedIdentity();
        $query = Lead::query()->when($knownLeadIds !== null, static fn (Builder $query): Builder => $query->whereKey($knownLeadIds));

        if (str_contains($identity, '@')) {

            $query->whereRaw('lower(trim(email)) = ?', [ $identity ]);

        } else {

            $phone = "regexp_replace(coalesce(phone, ''), '\\D', '', 'g')";

            $query->whereRaw(
                sprintf(
                    "case when length(%s) = 10 and trim(phone) not like '+%%' then concat('1', %s) else %s end = ?",
                    $phone,
                    $phone,
                    $phone,
                ),
                [ $identity ],
            );

        }

        return $query
            ->orderByDesc('id')
            ->offset(($search->page - 1) * CrmPageData::SIZE)
            ->limit(CrmPageData::SIZE + 1)
            ->get();
    }
}

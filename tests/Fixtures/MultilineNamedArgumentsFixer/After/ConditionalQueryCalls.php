<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MultilineNamedArgumentsFixer;

use Milewski\ECS\Tests\Support\ReflectionFluentModel;
use Milewski\ECS\Tests\Support\ReflectionFluentQuery as Builder;

final class EffectiveDate
{
    public function toDateString(): string
    {
        return '2026-09-19';
    }
}

final class FeeCandidate
{
    public bool $exists;

    public ?EffectiveDate $effective_to;

    public string $platform;

    public string $ad_account;

    public function getKey(): int
    {
        return 1;
    }
}

final class ConditionalQueryCalls
{
    public function query(FeeCandidate $candidate): Builder
    {
        return ReflectionFluentModel::query()
            ->when($candidate->exists, static fn (Builder $query): Builder => $query->whereKeyNot($candidate->getKey()))
            ->where('platform', $candidate->platform)
            ->where('ad_account', $candidate->ad_account)
            ->when(
                value: $candidate->effective_to !== null,
                callback: static fn (Builder $query): Builder => $query->where(
                    column: 'effective_from',
                    operator: '<=',
                    value: $candidate->effective_to?->toDateString(),
                ),
            );
    }
}

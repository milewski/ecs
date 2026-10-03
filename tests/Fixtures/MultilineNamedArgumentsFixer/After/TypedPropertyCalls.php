<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MultilineNamedArgumentsFixer;

use Milewski\ECS\Tests\Support\ReflectionMemoryRepository;

final class TypedPropertyCalls
{
    public function __construct(
        private readonly ReflectionMemoryRepository $memories,
    )
    {
    }

    public function forgetTopic(object $owner, string $query, int $limit): array
    {
        $matches = $this->memories->searchOwned(
            embedding: $this->embed($query),
            owner: $owner,
            limit: $limit,
        );

        $this->memories->deleteOwnedByIds(
            owner: $owner,
            ids: $matches,
        );

        return $matches;
    }

    private function embed(string $query): array
    {
        return [ $query ];
    }
}

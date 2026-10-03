<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Support;

final class ReflectionMemoryRepository
{
    public function searchOwned(array $embedding, object $owner, int $limit): array
    {
        return [];
    }

    public function deleteOwnedByIds(object $owner, array $ids): void
    {
    }
}

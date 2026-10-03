<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MultilineNamedArgumentsFixer;

use Milewski\ECS\Tests\Support\ReflectionFluentModel;
use Milewski\ECS\Tests\Support\ReflectionFluentQuery;

final class FluentQuery
{
    private function searchQuery(array $embedding): ReflectionFluentQuery
    {
        return ReflectionFluentModel::query()
            ->join('memories', 'memories.id', '=', 'memory_embeddings.memory_id')
            ->select([
                'memories.id as memory_id',
                'memories.owner_id',
                'memories.content as memory_content',
            ])
            ->selectRaw('1 - (memory_embeddings.embedding <=> ?) AS score', [ $embedding ])
            ->where('memories.status', 'indexed')
            ->whereRaw(
                sql: '1 - (memory_embeddings.embedding <=> ?) >= ?',
                bindings: [ $embedding, 0.75 ],
            );
    }
}

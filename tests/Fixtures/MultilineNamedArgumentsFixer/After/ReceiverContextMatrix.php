<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MultilineNamedArgumentsFixer;

use Milewski\ECS\Tests\Support\ReflectionDatabaseConnection;
use Milewski\ECS\Tests\Support\ReflectionMagicConnectionFacade;
use stdClass;

function reflected_connection(): ReflectionDatabaseConnection
{
    return ReflectionMagicConnectionFacade::connection('events');
}

function query_from_function(ReflectionDatabaseConnection $connection, object $context): void
{
    $connection->write(
        query: 'function query',
        bindings: [ 'context' => $context ],
        context: $context,
    );
}

trait QueriesFromTrait
{
    private function queryFromTrait(ReflectionDatabaseConnection $connection, object $context): void
    {
        $connection->write(
            query: 'trait query',
            bindings: [ 'nested' => [ $context ] ],
            context: $context,
        );
    }
}

final class QueryService
{
    use QueriesFromTrait;

    public function run(ReflectionDatabaseConnection $connection, object $context): void
    {
        $this->queryFromTrait($connection, $context);
    }
}

$context = new stdClass();
$connection = reflected_connection();
$magicConnection = ReflectionMagicConnectionFacade::connection('events');

$magicConnection->selectOne(
    query: 'magic return query',
    bindings: [ $context ],
);

$connection->write(
    query: 'top-level query',
    bindings: [ $context ],
    context: $context,
);

ReflectionMagicConnectionFacade::selectOne(
    query: 'direct facade query',
    bindings: [ $context ],
);

ReflectionMagicConnectionFacade::dispatch(
    callback: static fn (object $payload, array $metadata): object => $payload,
    options: [ 'objects' => [ $context ] ],
);

ReflectionMagicConnectionFacade::write(
    query: 'trait-provided magic query',
    bindings: [ 'objects' => [ $context ] ],
    context: $context,
);

query_from_function($connection, $context);

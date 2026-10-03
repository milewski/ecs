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
        'function query',
        [ 'context' => $context ],
        $context,
    );
}

trait QueriesFromTrait
{
    private function queryFromTrait(ReflectionDatabaseConnection $connection, object $context): void
    {
        $connection->write(
            'trait query',
            [ 'nested' => [ $context ] ],
            $context,
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
    'magic return query',
    [ $context ],
);

$connection->write(
    'top-level query',
    [ $context ],
    $context,
);

ReflectionMagicConnectionFacade::selectOne(
    'direct facade query',
    [ $context ],
);

ReflectionMagicConnectionFacade::dispatch(
    static fn (object $payload, array $metadata): object => $payload,
    [ 'objects' => [ $context ] ],
);

ReflectionMagicConnectionFacade::write(
    'trait-provided magic query',
    [ 'objects' => [ $context ] ],
    $context,
);

query_from_function($connection, $context);

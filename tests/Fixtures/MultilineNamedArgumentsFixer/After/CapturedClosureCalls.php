<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MultilineNamedArgumentsFixer;

use Milewski\ECS\Tests\Support\ReflectionDatabaseConnection;
use Milewski\ECS\Tests\Support\ReflectionMagicConnectionFacade;
use Milewski\ECS\Tests\Support\ReflectionPestTestCase;
use stdClass;

uses(ReflectionPestTestCase::class);

$connection = ReflectionMagicConnectionFacade::connection('events');
$directConnection = new ReflectionDatabaseConnection();
$reportingRole = 'reporting';

test('captured receivers retain their inferred type', function () use ($connection, $directConnection, $reportingRole): void {

    $directConnection->selectOne(
        query: 'directly constructed captured receiver',
        bindings: [ $reportingRole ],
    );

    $connection->transaction(function () use ($connection, $reportingRole): object {

        $attributes = $connection->selectOne(
            query: 'select * from pg_roles where rolname = ?',
            bindings: [ $reportingRole ],
        );

        return $attributes ?? new stdClass();

    });

    $lookup = static fn (): ?object => $connection->selectOne(
        query: 'select * from pg_roles where rolname in (?, ?)',
        bindings: [ new stdClass(), $reportingRole ],
    );

    unset($lookup);

});

return new class ()
{
    public function run(): void
    {
        $connection = ReflectionMagicConnectionFacade::connection('events');
        $reportingRole = 'reporting';

        retry(
            times: 5,
            callback: function () use ($connection, $reportingRole): void {

                $connection->transaction(function () use ($connection, $reportingRole): object {

                    $attributes = $connection->selectOne(
                        query: 'select rolsuper, rolcreatedb from pg_roles where rolname = ?',
                        bindings: [ $reportingRole ],
                    );

                    return $attributes ?? new stdClass();

                });

            },
        );
    }
};

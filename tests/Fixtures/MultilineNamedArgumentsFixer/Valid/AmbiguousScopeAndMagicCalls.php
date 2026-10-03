<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MultilineNamedArgumentsFixer;

use Milewski\ECS\Tests\Support\ReflectionDatabaseConnection;
use Milewski\ECS\Tests\Support\ReflectionMagicConnectionFacade;
use stdClass;

if (random_int(0, 1) === 1) {
    $conditionalConnection = new ReflectionDatabaseConnection();
}

$conditionalConnection->write(
    'conditional assignment is ambiguous',
    [ new stdClass() ],
    new stdClass(),
);

$ambiguousConnection = ReflectionMagicConnectionFacade::ambiguous('events');

$ambiguousConnection->write(
    'union magic return is ambiguous',
    [ new stdClass() ],
    new stdClass(),
);

$dynamicConnection = unknown_connection();
$callback = function () use ($dynamicConnection): void {

    $dynamicConnection->write(
        'unknown captured assignment is ambiguous',
        [ new stdClass() ],
        new stdClass(),
    );

};

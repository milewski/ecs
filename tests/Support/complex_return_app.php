<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Support;

use stdClass;

/**
 * Simulates Laravel's app() helper with a complex conditional return type.
 *
 * @template TClass of object
 *
 * @param class-string<TClass>|string|null $abstract
 *
 * @return ($abstract is class-string<TClass> ? TClass : ($abstract is null ? object : mixed))
 */
function complex_return_app(string|null $abstract = null): mixed
{
    return $abstract !== null ? new $abstract() : new stdClass();
}

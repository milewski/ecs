<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MultilineNamedArgumentsFixer;

function collect_values(string ...$values): array
{
    return $values;
}

function merge_values(array $values): array
{
    return $values;
}

final class UnresolvableCalls
{
    public function exercise(object $service, array $values): void
    {
        unknown_function(
            $values,
        );

        $service->unknownMethod(
            $values,
        );

        UnknownFactory::make(
            $values,
        );

        collect_values(
            'first',
            'second',
        );

        merge_values(
            ...$values,
        );

        merge_values(values: $values);
    }
}

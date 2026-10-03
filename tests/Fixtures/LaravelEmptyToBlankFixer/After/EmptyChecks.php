<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\LaravelEmptyToBlankFixer;

final class EmptyChecks
{
    public function check(mixed $value, array $data): array
    {
        $blank = blank($value);
        $filled = filled($value);
        $arrayBlank = blank($data[ 'key' ]);
        $arrayFilled = filled($data[ 'key' ]);

        return [ $blank, $filled, $arrayBlank, $arrayFilled ];
    }
}

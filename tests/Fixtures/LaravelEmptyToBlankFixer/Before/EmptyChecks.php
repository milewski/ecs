<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\LaravelEmptyToBlankFixer;

final class EmptyChecks
{
    public function check(mixed $value, array $data): array
    {
        $blank = empty($value);
        $filled = !empty($value);
        $arrayBlank = empty($data[ 'key' ]);
        $arrayFilled = !empty($data[ 'key' ]);

        return [ $blank, $filled, $arrayBlank, $arrayFilled ];
    }
}

<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\AutoImportClassesFixer;

use App\Support\CarbonCarbon;
use Carbon\Carbon as CarbonCarbon2;

final class AliasCollision
{
    public const array SERIALIZABLE_CLASSES = [
        CarbonCarbon::class,
        CarbonCarbon2::class,
        Carbon::class,
    ];
}

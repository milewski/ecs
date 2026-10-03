<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\AutoImportClassesFixer;

use Domain\Collection;

final class ConflictingClasses
{
    public const array SERIALIZABLE_CLASSES = [
        \Carbon\Carbon::class,
        Carbon::class,
    ];

    public function create(): array
    {
        return [
            Collection::new(),
            \Illuminate\Database\Eloquent\Collection::new(),
            \Vendor\One\Collection::new(),
            \Vendor\Two\Collection::new(),
        ];
    }
}

<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\AutoImportClassesFixer;

use Carbon\Carbon as CarbonCarbon;
use Domain\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Vendor\One\Collection as OneCollection;
use Vendor\Two\Collection as TwoCollection;

final class ConflictingClasses
{
    public const array SERIALIZABLE_CLASSES = [
        CarbonCarbon::class,
        Carbon::class,
    ];

    public function create(): array
    {
        return [
            Collection::new(),
            EloquentCollection::new(),
            OneCollection::new(),
            TwoCollection::new(),
        ];
    }
}

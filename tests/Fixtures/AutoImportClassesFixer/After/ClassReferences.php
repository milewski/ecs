<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\AutoImportClassesFixer;

use App\Data\Payload;
use App\Support\Serializer;
use Illuminate\Database\Eloquent\Collection;

final class ClassReferences
{
    public function create(Serializer $serializer): Collection
    {
        $serializer->serialize(new Payload());

        return Collection::new();
    }
}

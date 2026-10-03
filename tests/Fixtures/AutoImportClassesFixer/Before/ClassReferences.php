<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\AutoImportClassesFixer;

final class ClassReferences
{
    public function create(\App\Support\Serializer $serializer): \Illuminate\Database\Eloquent\Collection
    {
        $serializer->serialize(new \App\Data\Payload());

        return \Illuminate\Database\Eloquent\Collection::new();
    }
}

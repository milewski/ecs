<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Support;

final class ReflectionRecordManager
{
    public function update(object $record, object $data): object
    {
        return $record;
    }
}

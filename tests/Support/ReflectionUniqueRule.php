<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Support;

final class ReflectionUniqueRule
{
    public function where(mixed $column, mixed $value = null): self
    {
        return $this;
    }
}

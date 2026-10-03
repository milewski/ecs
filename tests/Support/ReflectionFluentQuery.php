<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Support;

final class ReflectionFluentQuery
{
    public function join(string $table, string $first, string $operator, string $second): self
    {
        return $this;
    }

    public function select(array $columns): self
    {
        return $this;
    }

    public function selectRaw(string $expression, array $bindings): self
    {
        return $this;
    }

    public function where(mixed $column, mixed $operator = null, mixed $value = null, string $boolean = 'and'): self
    {
        return $this;
    }

    public function whereKeyNot(mixed $id): self
    {
        return $this;
    }

    public function when(mixed $value, ?callable $callback = null, ?callable $default = null): self
    {
        return $this;
    }

    public function whereRaw(string $sql, array $bindings): self
    {
        return $this;
    }
}

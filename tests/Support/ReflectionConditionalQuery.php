<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Support;

/**
 * @mixin ReflectionFluentQuery
 */
final class ReflectionConditionalQuery
{
    public static function query(): self
    {
        return new self();
    }

    /**
     * @template TWhenReturnType
     *
     * @param callable($this, mixed): TWhenReturnType|null $callback
     * @param callable($this, mixed): TWhenReturnType|null $default
     *
     * @return $this|TWhenReturnType
     */
    public function when(mixed $value = null, ?callable $callback = null, ?callable $default = null)
    {
        if ($value) {
            return $callback($this, $value) ?? $this;
        }

        return $default === null ? $this : ($default($this, $value) ?? $this);
    }

    /**
     * @template TUnlessReturnType
     *
     * @param callable($this, mixed): TUnlessReturnType|null $callback
     * @param callable($this, mixed): TUnlessReturnType|null $default
     *
     * @return $this|TUnlessReturnType
     */
    public function unless(mixed $value = null, ?callable $callback = null, ?callable $default = null)
    {
        return $this->when(!$value, $callback, $default);
    }

    public function whereKey(array $ids): self
    {
        return $this;
    }
}

<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Support;

final class ReflectionRateLimit
{
    /**
     * @return static
     */
    public static function perMinute(int $maxAttempts)
    {
        return new self();
    }

    public function by(mixed $key): self
    {
        return $this;
    }
}

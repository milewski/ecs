<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Support;

final class ReflectionMcpTestResponse
{
    public function tool(object|string $tool, array $arguments = []): self
    {
        return $this;
    }

    public function assertHasErrors(): self
    {
        return $this;
    }

    public function assertSee(string $value): self
    {
        return $this;
    }
}

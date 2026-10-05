<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\FunctionParameterLayout;

final class CrmLoginData extends Data
{
    public function __construct(
        #[Min(1), Max(255)] public readonly string $username,
        #[Min(1), Max(255)] public readonly string $password,
    )
    {
    }
}

<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\DescriptiveVariableNameFixer;

use Vendor\Permissions\McpPromptPermission as PromptPermissionAlias;
use Vendor\Permissions\McpResourcePermission;
use Vendor\Permissions\McpToolPermission;

final class ArrowFunctions
{
    public function abilities(): array
    {
        return [
            ...array_map(static fn (McpResourcePermission $permission): string => $permission->ability(), McpResourcePermission::cases()),
            ...array_map(static fn (McpToolPermission $permission): string => $permission->ability(), McpToolPermission::cases()),
            ...array_map(static fn (McpResourcePermission $permission): string => $permission->ability(), McpResourcePermission::cases()),
            ...array_map(static fn (PromptPermissionAlias $permission): string => $permission->ability(), PromptPermissionAlias::cases()),
        ];
    }
}

<?php

declare(strict_types = 1);

namespace DigitalCreative\ECS\Tests\Fixtures\DescriptiveVariableNameFixer;

use Vendor\Permissions\McpPromptPermission as PromptPermissionAlias;
use Vendor\Permissions\McpResourcePermission;
use Vendor\Permissions\McpToolPermission;

final class ArrowFunctions
{
    public function abilities(): array
    {
        return [
            ...array_map(
                callback: static fn (McpResourcePermission $permission): string => $permission->ability(),
                array: McpResourcePermission::cases(),
            ),
            ...array_map(
                callback: static fn (McpToolPermission $permission): string => $permission->ability(),
                array: McpToolPermission::cases(),
            ),
            ...array_map(
                callback: static fn (McpResourcePermission $permission): string => $permission->ability(),
                array: McpResourcePermission::cases(),
            ),
            ...array_map(
                callback: static fn (PromptPermissionAlias $permission): string => $permission->ability(),
                array: PromptPermissionAlias::cases(),
            ),
        ];
    }
}

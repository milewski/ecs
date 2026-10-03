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
            ...array_map(static fn (McpResourcePermission $p): string => $p->ability(), McpResourcePermission::cases()),
            ...array_map(static fn (McpToolPermission $perm): string => $perm->ability(), McpToolPermission::cases()),
            ...array_map(static fn (McpResourcePermission $prm): string => $prm->ability(), McpResourcePermission::cases()),
            ...array_map(static fn (PromptPermissionAlias $mpp): string => $mpp->ability(), PromptPermissionAlias::cases()),
        ];
    }
}

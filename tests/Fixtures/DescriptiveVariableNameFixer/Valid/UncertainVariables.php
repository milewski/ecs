<?php

declare(strict_types = 1);

namespace DigitalCreative\ECS\Tests\Fixtures\DescriptiveVariableNameFixer;

use Vendor\Permissions\McpResourcePermission;
use Vendor\Permissions\McpToolPermission;
use Vendor\Permissions\Permission;
use Vendor\Tools\Tool;

final class UncertainVariables
{
    public function __construct(
        public readonly Permission $p,
    )
    {
    }

    public function callbacks(Permission $permission): array
    {
        $ambiguousUnion = static fn (Permission|Tool $p): object => $p;
        $nameCollision = static fn (Permission $p): string => sprintf('%s:%s', $permission->name(), $p->name());
        $duplicateInference = static fn (McpResourcePermission $p, McpToolPermission $t): string => sprintf(
            '%s:%s',
            $p->ability(),
            $t->ability(),
        );

        $alreadyDescriptive = static fn (Permission $resourcePermission): string => $resourcePermission->name();
        $unrelatedName = static fn (Permission $value): string => $value->name();
        $unknownType = static fn (self $s): self => $s;

        return [
            $ambiguousUnion,
            $nameCollision,
            $duplicateInference,
            $alreadyDescriptive,
            $unrelatedName,
            $unknownType,
        ];
    }
}

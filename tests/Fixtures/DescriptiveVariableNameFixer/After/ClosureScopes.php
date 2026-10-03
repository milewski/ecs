<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\DescriptiveVariableNameFixer;

use Closure;
use Vendor\Permissions\McpResourcePermission;
use Vendor\Permissions\McpToolPermission;

final class ClosureScopes
{
    public function callbacks(): array
    {
        $capturedByClosure = static function (McpResourcePermission &$permission): Closure {

            return static function () use (&$permission): string {
                return $permission->ability();
            };

        };

        $capturedByArrow = static fn (McpResourcePermission $permission): Closure => static fn (): string => $permission->ability();
        $shadowed = static fn (McpResourcePermission $permission): Closure => static fn (McpToolPermission $permission): string => $permission->ability();

        return [ $capturedByClosure, $capturedByArrow, $shadowed ];
    }
}

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
        $capturedByClosure = static function (McpResourcePermission &$p): Closure {

            return static function () use (&$p): string {
                return $p->ability();
            };

        };

        $capturedByArrow = static fn (McpResourcePermission $p): Closure => static fn (): string => $p->ability();
        $shadowed = static fn (McpResourcePermission $p): Closure => static fn (McpToolPermission $p): string => $p->ability();

        return [ $capturedByClosure, $capturedByArrow, $shadowed ];
    }
}

<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\DescriptiveVariableNameFixer;

use Vendor\Permissions\McpResourcePermission;
use Vendor\Permissions\McpToolPermission;
use Vendor\Repositories\PermissionRepositoryInterface;

final class TypeInference
{
    public function namedFunctionParameter(PermissionRepositoryInterface $pr): object
    {
        return $pr;
    }

    public function callbacks(): array
    {
        $nullable = static fn (?McpResourcePermission $p): string => $p?->ability() ?? '';
        $union = static fn (McpResourcePermission | McpToolPermission $perm): string => $perm->ability();
        $intersection = static fn (PermissionRepositoryInterface $pr): object => $pr;
        $fullyQualified = static fn (\Vendor\Permissions\McpResourcePermission $x): string => $x->ability();
        $string = static fn (string $str): string => $str;
        $callable = static fn (callable $cb): callable => $cb;

        return [ $nullable, $union, $intersection, $fullyQualified, $string, $callable ];
    }
}

<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\DescriptiveVariableNameFixer;

use Vendor\Permissions\McpResourcePermission;
use Vendor\Permissions\McpToolPermission;
use Vendor\Repositories\PermissionRepositoryInterface;

final class TypeInference
{
    public function namedFunctionParameter(PermissionRepositoryInterface $repository): object
    {
        return $repository;
    }

    public function callbacks(): array
    {
        $nullable = static fn (?McpResourcePermission $permission): string => $permission?->ability() ?? '';
        $union = static fn (McpResourcePermission|McpToolPermission $permission): string => $permission->ability();
        $intersection = static fn (PermissionRepositoryInterface $repository): object => $repository;
        $fullyQualified = static fn (McpResourcePermission $permission): string => $permission->ability();
        $string = static fn (string $string): string => $string;
        $callable = static fn (callable $callable): callable => $callable;

        return [ $nullable, $union, $intersection, $fullyQualified, $string, $callable ];
    }
}

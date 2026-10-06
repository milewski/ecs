<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MethodChainFixer;

final class PermissionChains
{
    public function forRole(?Role $role, Profile $profile): array
    {
        return $role === null ? $profile->permissions() : $role->permissions
            ->whereIn('name', array_column(CrmPermission::cases(), 'value'))
            ->pluck('name')
            ->all();
    }

    public function directMethods(Role $role): array
    {
        return $role->whereIn('name', array_column(CrmPermission::cases(), 'value'))
            ->pluck('name')
            ->all();
    }

    public function singleMethod(Role $role): array
    {
        return $role->permissions();
    }
}

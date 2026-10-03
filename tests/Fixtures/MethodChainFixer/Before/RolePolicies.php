<?php

declare(strict_types = 1);

namespace DigitalCreative\ECS\Tests\Fixtures\MethodChainFixer;

final class CrmRolePolicyData
{
    public function __construct(
        public readonly CrmRole $role,
        public readonly int $dailyLeadLimit,
        public readonly array $permissions,
    )
    {
    }
}

final class RolePolicies
{
    public function forRole(CrmRole $role, bool $lock = false): CrmRolePolicyData
    {
        $policy = CrmRolePolicy::query()->where('role', $role->value)->when($lock, static fn (Builder $query): Builder => $query->lockForUpdate())->firstOrFail();

        return new CrmRolePolicyData($role, $policy->daily_lead_limit, $policy->permissions);
    }
}

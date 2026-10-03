<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MethodChainFixer;

final class InlineConditions
{
    public function owners(?string $normalizedConfiguredPath, string $normalizedPath, object $connection): array
    {
        $pathOwners = [];

        if ($normalizedConfiguredPath !== null && $this->pathIsWithin($normalizedPath, $normalizedConfiguredPath)) {
            $pathOwners[] = $connection;
        }

        return $pathOwners;
    }

    public function membership(int $userId): object
    {
        if ($userId > 0) {

            $membership = CrmMembership::query()
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first() ?? new CrmMembership();

            $membership->save();

            return $membership;

        }

        return new CrmMembership();
    }

    public function findOrFail(int $id, bool $lock = false, ?array $knownLeadIds = null): Ticket
    {
        return $this->query()
            ->when($knownLeadIds !== null, static fn (Builder $query): Builder => $query->whereIn('lead_id', $knownLeadIds))
            ->when($lock, static fn (Builder $query): Builder => $query->lockForUpdate())
            ->findOrFail($id);
    }

    private function pathIsWithin(string $path, string $directory): bool
    {
        return str_starts_with($path, $directory);
    }
}

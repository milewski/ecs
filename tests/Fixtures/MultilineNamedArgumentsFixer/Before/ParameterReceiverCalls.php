<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MultilineNamedArgumentsFixer;

use Milewski\ECS\Tests\Support\ReflectionMemoryManager;

final class OAuthUser
{
}

final class MemoryWriteToolData
{
    public string $query;

    public ?int $limit;
}

final class ParameterReceiverCalls
{
    private function forget(
        OAuthUser $user,
        MemoryWriteToolData $data,
        ReflectionMemoryManager $manager,
        object $memories,
    ): array
    {
        return $manager->forgetTopic(
            $user,
            (string) $data->query,
            $data->limit ?? 10,
        );
    }
}

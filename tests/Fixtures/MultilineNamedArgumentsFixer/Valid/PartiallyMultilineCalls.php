<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MultilineNamedArgumentsFixer;

final class PartiallyMultilineCalls
{
    public function exercise(array $values): void
    {
        $this->call('db:seed', [
            '--class' => $this->option('seeder') ?: 'Database\\Seeders\\DatabaseSeeder',
            '--force' => true,
        ]);

        $this->singleArgument(
            $values,
        );
    }

    private function call(string $command, array $arguments): void
    {
    }

    private function singleArgument(array $values): void
    {
    }
}

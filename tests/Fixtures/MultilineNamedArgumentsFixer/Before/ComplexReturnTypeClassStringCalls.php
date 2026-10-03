<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MultilineNamedArgumentsFixer;

use Milewski\ECS\Tests\Support\ReflectionRecordManager as RecordManager;

use function Milewski\ECS\Tests\Support\complex_return_app;

abstract class ComplexReturnTypeClassStringCalls
{
    protected function handleRecordUpdate(RecordManager $record, array $data): RecordManager
    {
        return complex_return_app(RecordManager::class)->update(
            $record,
            LandingGroupData::fromFilament($data),
        );
    }

    protected function handleNestedUpdate(RecordManager $record, array $data): RecordManager
    {
        return $this->wrap(
            static fn (): RecordManager => complex_return_app(RecordManager::class)->update(
                $record,
                LandingGroupData::fromFilament($data),
            ),
        );
    }

    protected function wrap(\Closure $callback): mixed
    {
        return $callback();
    }
}

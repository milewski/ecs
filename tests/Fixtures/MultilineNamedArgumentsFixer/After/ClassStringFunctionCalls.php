<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MultilineNamedArgumentsFixer;

use Milewski\ECS\Tests\Support\ReflectionRecordManager as RecordManager;

function project_factory(string $implementation)
{
    return unknown_factory($implementation);
}

abstract class ClassStringFunctionCalls
{
    protected function handleRecordUpdate(object $record, array $data): object
    {
        /**
         * @var Experiment $record
         */
        return app(RecordManager::class)->update(
            record: $record,
            data: LandingGroupData::fromFilament($data),
        );
    }

    public function resolveFrameworkFunction(object $record, object $data): object
    {
        return \framework_resolve(service: RecordManager::class)->update(
            record: $record,
            data: $data,
        );
    }

    public function resolveWithArbitraryArgumentPosition(object $record, object $data): object
    {
        return custom_factory(options: [], implementation: RecordManager::class)->update(
            record: $record,
            data: $data,
        );
    }

    public function resolveProjectFactory(object $record, object $data): object
    {
        return project_factory(RecordManager::class)->update(
            record: $record,
            data: $data,
        );
    }
}

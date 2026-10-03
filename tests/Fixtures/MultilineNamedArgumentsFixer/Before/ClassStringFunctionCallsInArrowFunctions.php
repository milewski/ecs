<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MultilineNamedArgumentsFixer;

use Milewski\ECS\Tests\Support\ReflectionRecordManager as RecordManager;

abstract class ClassStringFunctionCallsInArrowFunctions
{
    protected function saveCampaignConfiguration(\Closure $callback): mixed
    {
        return $callback();
    }

    protected function handleRecordUpdate(RecordManager $record, array $data): RecordManager
    {
        /**
         * @var RecordManager $record
         */
        return $this->saveCampaignConfiguration(
            static fn (): RecordManager => app(RecordManager::class)->update(
                $record,
                LandingGroupData::fromFilament($data),
            ),
        );
    }

    protected function handleRecordUpdateArrowFn(RecordManager $record, array $data): RecordManager
    {
        return $this->saveCampaignConfiguration(
            fn (): RecordManager => app(RecordManager::class)->update(
                $record,
                LandingGroupData::fromFilament($data),
            ),
        );
    }

    public function chainedCallWithNestedArrowFn(): mixed
    {
        return retry(
            times: 5,
            callback: function (): mixed {
                return app(RecordManager::class)->update(
                    new RecordManager(),
                    new \stdClass(),
                );
            },
        );
    }
}

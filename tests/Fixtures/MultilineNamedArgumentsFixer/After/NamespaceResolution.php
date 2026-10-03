<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MultilineNamedArgumentsFixer\Library {

    function assemble(string $key, array $value): array
    {
        return [ $key => $value ];
    }

    final class Payload
    {
        public static function make(string $key, array $value): array
        {
            return [ $key => $value ];
        }
    }
}

namespace Milewski\ECS\Tests\Fixtures\MultilineNamedArgumentsFixer\Application {

    use function Milewski\ECS\Tests\Fixtures\MultilineNamedArgumentsFixer\Library\assemble as assemble_payload;

    use Milewski\ECS\Tests\Fixtures\MultilineNamedArgumentsFixer\Library\Payload as ImportedPayload;

    class BaseHandler
    {
        protected function handle(string $event, array $payload): void
        {
        }
    }

    final class Handler extends BaseHandler
    {
        public function run(array $payload): void
        {
            ImportedPayload::make(
                key: 'imported',
                value: $payload,
            );

            assemble_payload(
                key: 'aliased',
                value: $payload,
            );

            \Milewski\ECS\Tests\Fixtures\MultilineNamedArgumentsFixer\Library\assemble(
                key: 'qualified',
                value: $payload,
            );

            self::record(
                event: 'self',
                payload: $payload,
            );

            $this->handle(
                event: 'inherited',
                payload: $payload,
            );

            parent::handle(
                event: 'parent',
                payload: $payload,
            );
        }

        private static function record(string $event, array $payload): void
        {
        }
    }
}

<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MultilineNamedArgumentsFixer;

use function Milewski\ECS\Tests\Fixtures\MultilineNamedArgumentsFixer\build_payload as make_payload;

use PHPUnit\Framework\TestCase;
use RuntimeException;

function build_payload(string $prefix, array $payload): array
{
    return [ $prefix => $payload ];
}

final class DisqualifiedException extends RuntimeException
{
    public function __construct(
        public readonly string $gate,
        public readonly string $displayName,
        string $message,
    )
    {
        parent::__construct($message);
    }
}

final class FormAnswerValidationResultData
{
    public function __construct(
        public readonly array $answers,
        public readonly array $pii,
    )
    {
    }
}

final class PayloadFactory
{
    public static function make(array $payload, bool $strict): array
    {
        return $payload;
    }
}

final class MultilineCalls extends TestCase
{
    private array $openLoopPayloads = [];

    public function exercise(array $answers, object $submission): void
    {
        $definition = json_decode(
            json: file_get_contents(database_path('seeders/data/forms/definitions/medical-weight-loss-intake.json')),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        $handler = $this->beforePaymentHandler($submission, new DisqualifiedException(
            gate: 'state',
            displayName: 'State',
            message: 'Not eligible.',
        ));

        $result = new FormAnswerValidationResultData(
            answers: $answers,
            pii: $answers,
        );

        $payload = PayloadFactory::make(
            payload: $answers,
            strict: true,
        );

        $built = build_payload(
            prefix: 'form',
            payload: $payload,
        );

        $aliased = make_payload(
            prefix: 'aliased',
            payload: $built,
        );

        $runtimeException = new RuntimeException(
            message: 'Failed.',
            code: 500,
        );

        $this->assertSame(
            expected: [ 'Semaglutide injectable' ],
            actual: $this->openLoopPayloads[ 0 ][ 'data' ][ 'q25_medication_preference' ],
        );
    }

    private function beforePaymentHandler(object $submission, DisqualifiedException $exception): array
    {
        return [ $submission, $exception ];
    }
}

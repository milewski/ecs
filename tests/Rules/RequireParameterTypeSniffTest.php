<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Rules;

use Milewski\ECS\Tests\Support\EcsTestCase;
use Symplify\EasyCodingStandard\SniffRunner\Application\SniffFileProcessor;
use Symplify\EasyCodingStandard\SniffRunner\DataCollector\SniffMetadataCollector;
use Symplify\EasyCodingStandard\SniffRunner\ValueObject\Error\CodingStandardError;

final class RequireParameterTypeSniffTest extends EcsTestCase
{
    private const string ERROR_MESSAGE = 'Parameter "%s" is missing an explicit native type declaration. Inspect every caller and how the value is used, then declare the narrowest accurate type: prefer a specific class or interface, scalar, nullable, union, or intersection type. Do not add "mixed" merely to silence this error. Use "mixed" only when the API intentionally accepts values of any type and no narrower type or union can truthfully describe its contract.';

    public function provideConfig(): string
    {
        return __DIR__ . '/../Support/RequireParameterType.php';
    }

    public function test_reports_every_untyped_parameter_with_actionable_type_guidance(): void
    {
        $errors = $this->processFixture(__DIR__ . '/../Fixtures/RequireParameterType/untyped_request.php');
        $parameterNames = [ '$method', '$absUrl', '$headers', '$params', '$hasFile', '$apiMode', '$maxNetworkRetries' ];

        self::assertCount(count($parameterNames), $errors);

        foreach ($errors as $index => $error) {
            self::assertSame(sprintf(self::ERROR_MESSAGE, $parameterNames[ $index ]), $error->getMessage());
        }
    }

    public function test_accepts_explicit_native_parameter_types(): void
    {
        self::assertSame([], $this->processFixture(
            __DIR__ . '/../Fixtures/RequireParameterType/typed_request.php',
        ));
    }

    /**
     * @return list<CodingStandardError>
     */
    private function processFixture(string $fixture): array
    {
        $sniffFileProcessor = $this->make(SniffFileProcessor::class);
        $sniffMetadataCollector = $this->make(SniffMetadataCollector::class);

        self::assertNotEmpty($sniffFileProcessor->getCheckers(), 'The ECS configuration registered no sniffs.');

        $sniffMetadataCollector->reset();

        $sniffFileProcessor->processFileToString($fixture);

        return $sniffMetadataCollector->getCodingStandardErrors();
    }
}

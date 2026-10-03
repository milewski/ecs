<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Rules;

use Milewski\ECS\Tests\Support\EcsTestCase;
use Symplify\EasyCodingStandard\SniffRunner\Application\SniffFileProcessor;
use Symplify\EasyCodingStandard\SniffRunner\DataCollector\SniffMetadataCollector;
use Symplify\EasyCodingStandard\SniffRunner\ValueObject\Error\CodingStandardError;

final class ForbidSwitchStatementSniffTest extends EcsTestCase
{
    private const string ERROR_MESSAGE = 'Switch statements are forbidden. Use a match expression instead.';

    public function provideConfig(): string
    {
        return __DIR__ . '/../Support/ForbidSwitchStatement.php';
    }

    public function test_reports_every_switch_syntax_with_match_guidance(): void
    {
        $errors = $this->processFixture(
            __DIR__ . '/../Fixtures/ForbidSwitchStatement/switch_statements.php',
        );

        self::assertCount(2, $errors);

        foreach ($errors as $error) {
            self::assertSame(self::ERROR_MESSAGE, $error->getMessage());
        }
    }

    public function test_accepts_match_expressions(): void
    {
        self::assertSame([], $this->processFixture(
            __DIR__ . '/../Fixtures/ForbidSwitchStatement/match_expression.php',
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

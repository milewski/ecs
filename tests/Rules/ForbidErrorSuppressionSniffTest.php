<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Rules;

use Milewski\ECS\Sniffs\ForbidErrorSuppressionSniff;
use Milewski\ECS\Tests\Support\EcsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symplify\EasyCodingStandard\Parallel\ValueObject\Bridge;
use Symplify\EasyCodingStandard\SniffRunner\Application\SniffFileProcessor;
use Symplify\EasyCodingStandard\ValueObject\Configuration;

final class ForbidErrorSuppressionSniffTest extends EcsTestCase
{
    #[DataProvider('provideModes')]
    public function test_default_preset_rejects_error_suppression_in_check_and_fix_modes(bool $isFixer): void
    {
        $fixture = __DIR__ . '/../Fixtures/ForbidErrorSuppression/suppressed_errors.php';
        $original = file_get_contents($fixture);
        $processor = $this->make(SniffFileProcessor::class);
        $result = $processor->processFile($fixture, new Configuration(isFixer: $isFixer));
        $errors = $result[ Bridge::CODING_STANDARD_ERRORS ] ?? [];
        $expectedLines = [ 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 14 ];

        $this->assertCount(count($expectedLines), $errors);

        foreach ($errors as $index => $error) {

            $this->assertSame('Error suppression with @ is forbidden. Handle errors explicitly instead.', $error->getMessage());
            $this->assertSame(ForbidErrorSuppressionSniff::class . '.Found', $error->getCheckerClass());
            $this->assertSame($expectedLines[ $index ], $error->getLine());

        }

        $this->assertSame($original, file_get_contents($fixture));
    }

    public function test_accepts_unsuppressed_calls_and_at_signs_in_text(): void
    {
        $processor = $this->make(SniffFileProcessor::class);
        $result = $processor->processFile(
            __DIR__ . '/../Fixtures/ForbidErrorSuppression/allowed_code.php',
            new Configuration(isFixer: false),
        );

        $this->assertSame([], $result[ Bridge::CODING_STANDARD_ERRORS ] ?? []);
    }

    public static function provideModes(): iterable
    {
        yield 'check' => [ false ];

        yield 'fix' => [ true ];
    }
}

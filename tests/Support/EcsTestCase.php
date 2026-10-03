<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Support;

use RuntimeException;
use Symplify\EasyCodingStandard\FixerRunner\Application\FixerFileProcessor;
use Symplify\EasyCodingStandard\Testing\PHPUnit\AbstractCheckerTestCase;

abstract class EcsTestCase extends AbstractCheckerTestCase
{
    private FixerFileProcessor $fixerFileProcessor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixerFileProcessor = $this->make(FixerFileProcessor::class);
    }

    public function provideConfig(): string
    {
        return dirname(__DIR__, 2) . '/src/Custom.php';
    }

    final protected function assertFixtureIsFixedTo(string $inputFixture, string $expectedFixture): void
    {
        $this->assertCodeIsFixedTo(
            input: $this->readFixture($inputFixture),
            expected: $this->readFixture($expectedFixture),
            temporaryFilename: basename($inputFixture),
        );
    }

    final protected function assertLaravelFixtureIsFixedTo(string $inputFixture, string $expectedFixture): void
    {
        $this->assertCodeIsFixedTo(
            input: $this->readFixture($inputFixture),
            expected: $this->readFixture($expectedFixture),
            temporaryFilename: basename($inputFixture),
            laravelProject: true,
        );
    }

    final protected function assertFixturePasses(string $fixture): void
    {
        $source = $this->readFixture($fixture);

        $this->assertCodeIsFixedTo($source, $source, basename($fixture));
    }

    final protected function assertCodeIsFixedTo(string $input, string $expected, string $temporaryFilename = 'fixture.php', bool $laravelProject = false): void
    {
        $input = $this->normalizeLineEndings($input);
        $expected = $this->normalizeLineEndings($expected);

        self::assertNotEmpty($this->fixerFileProcessor->getCheckers(), 'The ECS configuration registered no fixers.');

        $temporaryDirectory = sprintf(
            '%s/milewski-ecs-tests/%s',
            sys_get_temp_dir(),
            bin2hex(random_bytes(16)),
        );

        if (mkdir($temporaryDirectory, recursive: true) === false && is_dir($temporaryDirectory) === false) {
            throw new RuntimeException(sprintf('Unable to create temporary directory "%s".', $temporaryDirectory));
        }

        if ($laravelProject) {
            $this->createLaravelApplicationFiles($temporaryDirectory);
        }

        $temporaryFile = sprintf('%s/%s', $temporaryDirectory, $temporaryFilename);

        if (file_put_contents($temporaryFile, $input) === false) {
            throw new RuntimeException(sprintf('Unable to write temporary fixture "%s".', $temporaryFile));
        }

        try {

            self::assertSame(
                expected: $expected,
                actual: $this->normalizeLineEndings($this->fixerFileProcessor->processFileToString($temporaryFile)),
            );

        } finally {

            unlink($temporaryFile);

            if ($laravelProject) {

                unlink(sprintf('%s/artisan', $temporaryDirectory));
                unlink(sprintf('%s/bootstrap/app.php', $temporaryDirectory));
                rmdir(sprintf('%s/bootstrap', $temporaryDirectory));

            }

            rmdir($temporaryDirectory);

        }
    }

    private function createLaravelApplicationFiles(string $temporaryDirectory): void
    {
        $bootstrapDirectory = sprintf('%s/bootstrap', $temporaryDirectory);

        if (mkdir($bootstrapDirectory) === false
            || file_put_contents(sprintf('%s/artisan', $temporaryDirectory), '') === false
            || file_put_contents(sprintf('%s/app.php', $bootstrapDirectory), "<?php\n") === false) {
            throw new RuntimeException('Unable to create temporary Laravel application files.');
        }
    }

    private function readFixture(string $fixture): string
    {
        $contents = file_get_contents($fixture);

        if ($contents === false) {
            throw new RuntimeException(sprintf('Unable to read fixture "%s".', $fixture));
        }

        return $contents;
    }

    private function normalizeLineEndings(string $code): string
    {
        return str_replace([ "\r\n", "\r" ], "\n", $code);
    }
}

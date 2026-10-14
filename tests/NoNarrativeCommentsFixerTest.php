<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests;

use Milewski\ECS\Fixers\NoNarrativeCommentsFixer;
use Milewski\ECS\Tests\Support\EcsTestCase;
use PhpCsFixer\Tokenizer\Tokens;
use RuntimeException;
use SplFileInfo;

final class NoNarrativeCommentsFixerTest extends EcsTestCase
{
    public function test_narrative_comments_are_removed_without_changing_executable_tokens(): void
    {
        $source = <<<'PHP'
        <?php
        // Explanation.
        # Another explanation.
        /** A narrative document. */
        $result = new/** explanation */\stdClass();
        $value = 'https://example.test/*literal*/';
        /* A block.
         * With several lines.
         */
        PHP;

        $fixed = $this->fix($source);

        $this->assertStringNotContainsString('Explanation.', $fixed);
        $this->assertStringNotContainsString('Another explanation.', $fixed);
        $this->assertStringNotContainsString('A narrative document.', $fixed);
        $this->assertStringContainsString("'https://example.test/*literal*/'", $fixed);
        $this->assertSame($this->executableTokens($source), $this->executableTokens($fixed));
    }

    public function test_collection_shapes_and_type_annotations_retain_metadata_without_descriptions(): void
    {
        $source = <<<'PHP'
        <?php
        /**
         * A description.
         * @template TItem of object A template explanation.
         * @param array{
         *   items: list<TItem>,
         *   cursor?: string|null
         * } $input Input explanation.
         * @return \Illuminate\Support\Collection<int, TItem> Result explanation.
         * @see https://example.test/narrative
         */
        function transform(array $input): object {}
        PHP;

        $fixed = $this->fix($source);

        $this->assertStringContainsString('@template TItem of object', $fixed);
        $this->assertStringContainsString('cursor?: string|null', $fixed);
        $this->assertStringContainsString('} $input', $fixed);
        $this->assertStringContainsString('@return \Illuminate\Support\Collection<int, TItem>', $fixed);
        $this->assertStringNotContainsString('explanation.', $fixed);
        $this->assertStringNotContainsString('A description.', $fixed);
        $this->assertStringNotContainsString('@see', $fixed);
        $this->assertSame($fixed, $this->fix($fixed));
    }

    public function test_metadata_only_comments_and_machine_flags_are_idempotent(): void
    {
        $source = <<<'PHP'
        <?php
        /** @deprecated Use a newer entry. */
        class Example {}
        /** @var list<string> $values Value explanation. */
        $values = [];
        PHP;

        $fixed = $this->fix($source);

        $this->assertStringContainsString('@deprecated', $fixed);
        $this->assertStringNotContainsString('Use a newer entry.', $fixed);
        $this->assertStringContainsString('@var list<string> $values', $fixed);
        $this->assertStringNotContainsString('Value explanation.', $fixed);
        $this->assertSame($fixed, $this->fix($fixed));
    }

    public function test_invalid_type_metadata_is_rejected_instead_of_silently_deleted(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid PHPDoc metadata');

        $this->fix('<?php /** @param array{ $input */ function run(array $input): void {}');
    }

    public function test_comment_free_source_is_unchanged(): void
    {
        $source = '<?php $value = "// literal";';

        $this->assertSame($source, $this->fix($source));
    }

    public function test_required_directives_retain_their_targets_without_optional_reasons(): void
    {
        $source = <<<'PHP'
        <?php
        // phpcs:disable Generic.NamingConventions -- A narrative reason.
        // @phpstan-ignore variable.undefined (A narrative reason.)
        /** @psalm-suppress MixedAssignment */
        $value = 1;
        // @codeCoverageIgnoreStart
        $other = 2;
        // @codeCoverageIgnoreEnd
        PHP;

        $fixed = $this->fix($source);

        $this->assertStringContainsString('phpcs:disable Generic.NamingConventions', $fixed);
        $this->assertStringContainsString('@phpstan-ignore variable.undefined', $fixed);
        $this->assertStringContainsString('@psalm-suppress MixedAssignment', $fixed);
        $this->assertStringContainsString('@codeCoverageIgnoreStart', $fixed);
        $this->assertStringContainsString('@codeCoverageIgnoreEnd', $fixed);
        $this->assertStringNotContainsString('A narrative reason.', $fixed);
        $this->assertSame($this->executableTokens($source), $this->executableTokens($fixed));
        $this->assertSame($fixed, $this->fix($fixed));
    }

    public function test_metadata_only_type_layout_is_preserved(): void
    {
        $source = <<<'PHP'
        <?php
        /**
         * @template TItem of object
         *
         * @param array<string, list<TItem>> $input
         * @return \Illuminate\Support\Collection<int, TItem>|null
         */
        function transform(array $input): ?object {}
        PHP;

        $this->assertSame($source, $this->fix($source));
    }

    public function test_type_metadata_in_regular_comments_is_retained_without_prose(): void
    {
        $source = <<<'PHP'
        <?php
        // @var list<string> $values A narrative explanation.
        $values = [];
        /* @var \Illuminate\Support\Collection<int, object> $records A narrative explanation. */
        $records = null;
        PHP;

        $fixed = $this->fix($source);

        $this->assertStringContainsString('@var list<string> $values', $fixed);
        $this->assertStringContainsString('@var \Illuminate\Support\Collection<int, object> $records', $fixed);
        $this->assertStringNotContainsString('A narrative explanation.', $fixed);
        $this->assertSame($this->executableTokens($source), $this->executableTokens($fixed));
        $this->assertSame($fixed, $this->fix($fixed));
    }

    private function fix(string $source): string
    {
        $tokens = Tokens::fromCode($source);
        $fixer = new NoNarrativeCommentsFixer();
        $fixer->fix(new SplFileInfo('fixture.php'), $tokens);

        return $tokens->generateCode();
    }

    private function executableTokens(string $source): array
    {
        $tokens = array_filter(token_get_all($source, TOKEN_PARSE), static function (array|string $token): bool {

            if (is_array($token) === false) {
                return true;
            }

            return in_array($token[ 0 ], [ T_COMMENT, T_DOC_COMMENT, T_WHITESPACE ], true) === false;

        });

        return array_values(array_map(
            callback: static fn (array|string $token): array|string => is_array($token) ? [ $token[ 0 ], $token[ 1 ] ] : $token,
            array: $tokens,
        ));
    }
}

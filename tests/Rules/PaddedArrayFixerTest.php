<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Rules;

use Milewski\ECS\Fixers\PaddedArrayFixer;
use Milewski\ECS\Tests\Support\FixerTestCase;
use PhpCsFixer\ConfigurationException\InvalidFixerConfigurationException;
use PhpCsFixer\Fixer\ControlStructure\TrailingCommaInMultilineFixer;
use PhpCsFixer\Fixer\Whitespace\ArrayIndentationFixer;
use PhpCsFixer\Fixer\Whitespace\StatementIndentationFixer;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use SplFileInfo;

final class PaddedArrayFixerTest extends FixerTestCase
{
    #[DataProvider('provideArrays')]
    public function test_multiline_array_layout(string $input, string $expected): void
    {
        $input = "<?php\n" . $input . ";\n";
        $expected = "<?php\n" . $expected . ";\n";

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public static function provideArrays(): array
    {
        return [
            'single multiline object' => [
                "\$items = [ new Item(\n    first: 1,\n    second: 2,\n) ]",
                "\$items = [\n    new Item(\n        first: 1,\n        second: 2,\n    ),\n]",
            ],
            'multiple multiline objects and inline elements' => [
                "\$items = [ new Item(\n    first: 1,\n    second: 2,\n), new Item(\n    first: 3,\n    second: 4,\n), null ]",
                "\$items = [\n    new Item(\n        first: 1,\n        second: 2,\n    ),\n    new Item(\n        first: 3,\n        second: 4,\n    ),\n    null,\n]",
            ],
            'grouped scalar elements' => [
                "\$items = [\n    1, 2,\n    3, 4\n]",
                "\$items = [\n    1,\n    2,\n    3,\n    4,\n]",
            ],
            'associative nested arrays' => [
                "\$items = [ 'rows' => [\n    'first' => 1, 'second' => 2,\n], 'empty' => [] ]",
                "\$items = [\n    'rows' => [\n        'first' => 1,\n        'second' => 2,\n    ],\n    'empty' => [],\n]",
            ],
            'array unpacking and nested call commas' => [
                "\$items = [\n    ...\$defaults, pair(1, 2), 'a,b'\n]",
                "\$items = [\n    ...\$defaults,\n    pair(1, 2),\n    'a,b',\n]",
            ],
            'line and block comments' => [
                "\$items = [\n    1, 2, // Keep this with 2.\n    /* Explain 3. */ 3, 4,\n]",
                "\$items = [\n    1,\n    2, // Keep this with 2.\n    /* Explain 3. */ 3,\n    4,\n]",
            ],
            'comments without elements' => [
                "\$items = [ // Reserved for future values.\n]",
                "\$items = [\n    // Reserved for future values.\n]",
            ],
            'line comment before the next element' => [
                "\$items = [\n    1,\n    // Explain 2.\n    2,\n]",
                "\$items = [\n    1,\n    // Explain 2.\n    2,\n]",
            ],
            'literal newlines are preserved' => [
                "\$items = [ \"first line\nsecond line\", 2 ]",
                "\$items = [\n    \"first line\nsecond line\",\n    2,\n]",
            ],
            'compact inline array stays compact' => [
                '$items = [1, 2]',
                '$items = [ 1, 2 ]',
            ],
            'empty array' => [ '$items = [   ]', '$items = []' ],
            'adjacent arrays are both padded' => [ '$items = [[1], [2]]', '$items = [ [ 1 ], [ 2 ] ]' ],
            'array offsets stay offsets' => [ '$value = $items[0][1]', '$value = $items[ 0 ][ 1 ]' ],
            'destructuring stays destructuring' => [ '[ $first, $second ] = $items', '[ $first, $second ] = $items' ],
        ];
    }

    #[DataProvider('provideLineLengthCases')]
    public function test_inline_arrays_respect_line_length(string $input, string $expected, int $maximumLength): void
    {
        $input = "<?php\n" . $input . ";\n";
        $expected = "<?php\n" . $expected . ";\n";

        $this->assertSame($expected, $this->fix($input, maximumLength: $maximumLength));
        $this->assertSame($expected, $this->fix($expected, maximumLength: $maximumLength));
    }

    public static function provideLineLengthCases(): array
    {
        $compact = '$items = [ 1, 2 ]';
        $length = strlen($compact . ';');

        return [
            'below limit' => [ $compact, $compact, $length + 1 ],
            'at limit' => [ $compact, $compact, $length ],
            'above limit' => [ $compact, "\$items = [\n    1,\n    2,\n]", $length - 1 ],
            'bracket padding counts toward the limit' => [
                '$items = [1,2]',
                "\$items = [\n    1,\n    2,\n]",
                $length - 2,
            ],
            'nested arrays independently expand' => [
                '$items = [ \'rows\' => [ \'first\' => 11111, \'second\' => 22222 ], \'empty\' => [] ]',
                "\$items = [\n    'rows' => [\n        'first' => 11111,\n        'second' => 22222,\n    ],\n    'empty' => [],\n]",
                45,
            ],
            'short nested arrays stay inline' => [
                '$items = [ \'rows\' => [ 1, 2 ], \'other\' => [ 3, 4 ] ]',
                "\$items = [\n    'rows' => [ 1, 2 ],\n    'other' => [ 3, 4 ],\n]",
                35,
            ],
            'commas inside calls and strings' => [
                '$items = [ ...$defaults, pair(1, 2), \'first,second,third\' ]',
                "\$items = [\n    ...\$defaults,\n    pair(1, 2),\n    'first,second,third',\n]",
                35,
            ],
            'block comments retain their contents' => [
                '$items = [ 1, /* second,third */ 2, 3 ]',
                "\$items = [\n    1,\n    /* second,third */ 2,\n    3,\n]",
                35,
            ],
            'single long element' => [
                '$items = [ \'an indivisible long string literal\' ]',
                "\$items = [\n    'an indivisible long string literal',\n]",
                35,
            ],
            'empty array remains empty' => [ '$items = []', '$items = []', 10 ],
            'array offsets are never expanded' => [
                '$value = $items[ \'a long array offset key\' ]',
                '$value = $items[ \'a long array offset key\' ]',
                20,
            ],
            'destructuring is never expanded' => [
                '[ $firstLongName, $secondLongName ] = $items',
                '[ $firstLongName, $secondLongName ] = $items',
                20,
            ],
        ];
    }

    public function test_long_inline_arrays_respect_tabs_and_crlf(): void
    {
        $input = "<?php\r\nfunction example()\r\n{\r\n\treturn [ 'first' => 1, 'second' => 2 ];\r\n}\r\n";
        $expected = "<?php\r\nfunction example()\r\n{\r\n\treturn [\r\n\t\t'first' => 1,\r\n\t\t'second' => 2,\r\n\t];\r\n}\r\n";
        $whitespaces = new WhitespacesFixerConfig("\t", "\r\n");

        $this->assertSame($expected, $this->fix($input, $whitespaces, maximumLength: 30));
        $this->assertSame($expected, $this->fix($expected, $whitespaces, maximumLength: 30));
    }

    #[DataProvider('provideInvalidLimits')]
    public function test_invalid_line_length_limits_are_rejected(mixed $limit): void
    {
        $fixer = new PaddedArrayFixer();

        $this->expectException(InvalidFixerConfigurationException::class);

        $fixer->configure([ 'max_line_length' => $limit ]);
    }

    public static function provideInvalidLimits(): array
    {
        return [
            'zero' => [ 0 ],
            'negative' => [ -1 ],
            'numeric string' => [ '140' ],
            'float' => [ 140.0 ],
        ];
    }

    public function test_layout_respects_tabs_and_crlf(): void
    {
        $input = "<?php\r\nfunction example()\r\n{\r\n\t\$items = [ new Item(\r\n\t\tfirst: 1,\r\n\t\tsecond: 2,\r\n\t) ];\r\n}\r\n";
        $expected = "<?php\r\nfunction example()\r\n{\r\n\t\$items = [\r\n\t\tnew Item(\r\n\t\t\tfirst: 1,\r\n\t\t\tsecond: 2,\r\n\t\t),\r\n\t];\r\n}\r\n";
        $whitespaces = new WhitespacesFixerConfig("\t", "\r\n");

        $this->assertSame($expected, $this->fix($input, $whitespaces));
        $this->assertSame($expected, $this->fix($expected, $whitespaces));
    }

    public function test_multiline_callbacks_and_match_arms_keep_their_internal_commas(): void
    {
        $input = <<<'PHP'
        <?php
        $items = [ static function ($first, $second) {
            return pair($first, $second);
        }, match ($status) {
            'first', 'second' => pair(1, 2),
            default => null,
        } ];
        PHP;

        $expected = <<<'PHP'
        <?php
        $items = [
            static function ($first, $second) {
                return pair($first, $second);
            },
            match ($status) {
                'first', 'second' => pair(1, 2),
                default => null,
            },
        ];
        PHP;

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    private function fix(string $input, ?WhitespacesFixerConfig $whitespaces = null, int $maximumLength = 140): string
    {
        $tokens = Tokens::fromCode($input);
        $arrayFixer = new PaddedArrayFixer();
        $arrayFixer->configure([ 'max_line_length' => $maximumLength ]);

        $trailingCommaFixer = new TrailingCommaInMultilineFixer();
        $trailingCommaFixer->configure([ 'elements' => [ 'arrays' ] ]);

        foreach ([ $arrayFixer, new ArrayIndentationFixer(), $trailingCommaFixer, new StatementIndentationFixer() ] as $fixer) {

            if ($whitespaces !== null) {

                if ($fixer instanceof WhitespacesAwareFixerInterface) {
                    $fixer->setWhitespacesConfig($whitespaces);
                }

            }

            $fixer->fix(new SplFileInfo('fixture.php'), $tokens);

            $tokens->clearEmptyTokens();

        }

        return $tokens->generateCode();
    }
}

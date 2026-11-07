<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Rules;

use Milewski\ECS\Fixers\MultilineNamedArgumentsFixer;
use Milewski\ECS\Tests\Support\FixerTestCase;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use SplFileInfo;

final class PestTestCallLayoutTest extends FixerTestCase
{
    #[DataProvider('provideTestHelpers')]
    public function test_long_test_headers_stay_inline(string $helper): void
    {
        $input = "<?php\n" . $helper . <<<'PHP'
        ('a patient a media buyer brought in who buys through a remarketing link is charged by both', function () use ($run, $visitor, $sale, $fee, $organic): void {
            $this->assertTrue(true);
        });

        PHP;

        $this->assertSame($input, $this->fix($input));
    }

    #[DataProvider('provideTestHelpers')]
    public function test_expanded_named_test_headers_become_inline_and_positional(string $helper): void
    {
        $input = "<?php\n" . $helper . <<<'PHP'
        (
            description: 'a patient a media buyer brought in who buys through a remarketing link is charged by both',
            closure: function () use ($run, $visitor, $sale, $fee, $organic): void {
                $this->assertTrue(true);
            },
        );

        PHP;

        $expected = "<?php\n" . $helper . <<<'PHP'
        ('a patient a media buyer brought in who buys through a remarketing link is charged by both', function () use ($run, $visitor, $sale, $fee, $organic): void {
                $this->assertTrue(true);
            });

        PHP;

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public static function provideTestHelpers(): array
    {
        return [
            'test' => [ 'test' ],
            'it' => [ 'it' ],
            'fully qualified test' => [ '\\test' ],
            'fully qualified it' => [ '\\it' ],
            'case insensitive test' => [ 'TEST' ],
        ];
    }

    #[DataProvider('provideArgumentLayouts')]
    public function test_test_arguments_keep_their_binding(string $input, string $expected): void
    {
        $input = "<?php\n" . $input;
        $expected = "<?php\n" . $expected;

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public static function provideArgumentLayouts(): array
    {
        return [
            'positional arguments' => [
                "test(\n    'description',\n    function (): void {},\n);",
                "test('description', function (): void {});",
            ],
            'mixed arguments' => [
                "test(\n    'description',\n    closure: function (): void {},\n);",
                "test('description', function (): void {});",
            ],
            'inline named arguments' => [
                "test(description: 'description', closure: function (): void {});",
                "test('description', function (): void {});",
            ],
            'higher order test' => [
                "test(\n    description: 'description',\n)->get('/');",
                "test('description')->get('/');",
            ],
            'empty helper call' => [ 'test();', 'test();' ],
            'first class callable' => [ 'test(...);', 'test(...);' ],
            'arrow function' => [
                "test(\n    description: 'description',\n    closure: fn (): bool => true,\n);",
                "test('description', fn (): bool => true);",
            ],
            'literal newline in the description' => [
                "test(\n    description: 'first line\nsecond line',\n    closure: function (): void {},\n);",
                "test('first line\nsecond line', function (): void {});",
            ],
            'reordered named arguments' => [
                "test(\n    closure: make_closure(),\n    description: make_description(),\n);",
                "test(\n    closure: make_closure(),\n    description: make_description(),\n);",
            ],
            'unknown named argument' => [
                "test(\n    custom: 'description',\n    closure: function (): void {},\n);",
                "test(\n    custom: 'description',\n    closure: function (): void {},\n);",
            ],
            'unpacked arguments' => [
                "test(\n    ...\$arguments,\n);",
                "test(\n    ...\$arguments,\n);",
            ],
            'header comment' => [
                "test(\n    'description', // Keep this attached to the description.\n    function (): void {},\n);",
                "test(\n    'description', // Keep this attached to the description.\n    function (): void {},\n);",
            ],
        ];
    }

    public function test_calls_inside_test_closures_are_still_formatted(): void
    {
        $input = <<<'PHP'
        <?php
        test('description', function (): void {
            // Preserve this body comment.
            unknown_function($firstLongArgumentValueForThisCall, $secondLongArgumentValueForThisCall);
        });
        PHP;

        $expected = <<<'PHP'
        <?php
        test('description', function (): void {
            // Preserve this body comment.
            unknown_function(
                $firstLongArgumentValueForThisCall,
                $secondLongArgumentValueForThisCall
            );
        });
        PHP;

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    #[DataProvider('provideOtherCallables')]
    public function test_other_callables_named_test_are_formatted_normally(string $callable): void
    {
        $input = "<?php\n" . $callable . '($firstLongArgumentValueForThisCall, $secondLongArgumentValueForThisCall);';
        $expected = "<?php\n" . $callable . "(\n    \$firstLongArgumentValueForThisCall,\n    \$secondLongArgumentValueForThisCall\n);";

        $this->assertSame($expected, $this->fix($input));
    }

    public static function provideOtherCallables(): array
    {
        return [
            'method' => [ '$service->test' ],
            'nullsafe method' => [ '$service?->test' ],
            'static method' => [ 'Service::test' ],
            'constructor' => [ 'new Test' ],
            'namespaced function' => [ 'Custom\\test' ],
            'fully qualified namespaced function' => [ '\\Custom\\test' ],
            'relative namespaced function' => [ 'namespace\\test' ],
            'variable function' => [ '$test' ],
        ];
    }

    public function test_compaction_preserves_crlf_and_tabs_inside_the_closure(): void
    {
        $input = "<?php\r\n\ttest(\r\n\t\tdescription: 'description',\r\n\t\tclosure: function (): void {\r\n\t\t\t\$this->assertTrue(true);\r\n\t\t},\r\n\t);";
        $expected = "<?php\r\n\ttest('description', function (): void {\r\n\t\t\t\$this->assertTrue(true);\r\n\t\t});";

        $this->assertSame($expected, $this->fix($input, new WhitespacesFixerConfig("\t", "\r\n")));
    }

    private function fix(string $input, ?WhitespacesFixerConfig $whitespaces = null): string
    {
        $tokens = Tokens::fromCode($input);
        $fixer = new MultilineNamedArgumentsFixer();
        $fixer->configure([ 'max_line_length' => 60 ]);

        if ($whitespaces !== null) {
            $fixer->setWhitespacesConfig($whitespaces);
        }

        $fixer->fix(new SplFileInfo('fixture.php'), $tokens);

        return $tokens->generateCode();
    }
}

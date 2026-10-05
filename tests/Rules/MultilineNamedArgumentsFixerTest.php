<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Rules;

use Milewski\ECS\Fixers\MultilineNamedArgumentsFixer;
use Milewski\ECS\Tests\Support\FixerTestCase;
use PhpCsFixer\ConfigurationException\InvalidFixerConfigurationException;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use SplFileInfo;

final class MultilineNamedArgumentsFixerTest extends FixerTestCase
{
    #[DataProvider('provideExpandedCalls')]
    public function test_expanded_calls_place_each_top_level_argument_on_its_own_line(string $callable): void
    {
        $input = "<?php\n" . $callable . "(\n    first: 1, second: 2,\n    third: 3, fourth: 4);\n";
        $expected = "<?php\n" . $callable . "(\n    first: 1,\n    second: 2,\n    third: 3,\n    fourth: 4\n);\n";

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public static function provideExpandedCalls(): array
    {
        return [
            'constructor' => [ 'new UnknownData' ],
            'function' => [ 'unknown_function' ],
            'static method' => [ 'UnknownFactory::make' ],
            'instance method' => [ '$service->make' ],
            'nullsafe method' => [ '$service?->make' ],
            'variable callable' => [ '$factory' ],
            'array callable' => [ '$callbacks[\'make\']' ],
        ];
    }

    public function test_grouped_positional_arguments_keep_their_binding_when_names_are_resolved(): void
    {
        $declaration = "<?php\nfunction combine(string \$first, string \$second, string \$third): void {}\n";
        $input = $declaration . "combine(\n    'one', 'two', third: 'three',\n);\n";
        $expected = $declaration . "combine(\n    first: 'one',\n    second: 'two',\n    third: 'three',\n);\n";

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public function test_normalizing_grouped_arguments_preserves_nested_expressions_and_comments(): void
    {
        $input = <<<'PHP'
        <?php
        unknown_call(
            'commas, parentheses ()', [ 1, 2 ], // Keep the array explanation.
            /* Callback description. */ static fn ($first, $second) => [$first, $second], value: nested(1, 2),
        );
        PHP;

        $expected = <<<'PHP'
        <?php
        unknown_call(
            'commas, parentheses ()',
            [ 1, 2 ], // Keep the array explanation.
            /* Callback description. */ static fn ($first, $second) => [$first, $second],
            value: nested(1, 2),
        );
        PHP;

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public function test_unknown_variadic_and_unpacked_grouped_calls_keep_positional_arguments(): void
    {
        $input = <<<'PHP'
        <?php
        function collect_values(string ...$values): array { return $values; }
        collect_values(
            'one', 'two',
        );
        unknown_call(
            $first, ...$remaining,
        );
        PHP;

        $expected = <<<'PHP'
        <?php
        function collect_values(string ...$values): array { return $values; }
        collect_values(
            'one',
            'two',
        );
        unknown_call(
            $first,
            ...$remaining,
        );
        PHP;

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public function test_grouped_arguments_respect_tabs_and_crlf(): void
    {
        $input = "<?php\r\n\tunknown_call(\r\n\t\tfirst: 1, second: 2,\r\n\t);\r\n";
        $expected = "<?php\r\n\tunknown_call(\r\n\t\tfirst: 1,\r\n\t\tsecond: 2,\r\n\t);\r\n";
        $whitespaces = new WhitespacesFixerConfig("\t", "\r\n");

        $this->assertSame($expected, $this->fix($input, 140, $whitespaces));
        $this->assertSame($expected, $this->fix($expected, 140, $whitespaces));
    }

    #[DataProvider('providePartialArgumentLayouts')]
    public function test_partially_expanded_argument_lists_are_completed(string $input, bool $hasComment = false): void
    {
        $expected = "<?php\nunknown_call(\n    first: 1,\n    second: 2,\n    third: 3\n);";

        if ($hasComment) {
            $expected = str_replace('    first:', "    /* Values */\n    first:", $expected);
        }

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public static function providePartialArgumentLayouts(): array
    {
        return [
            'inline first argument' => [ "<?php\nunknown_call(first: 1,\n    second: 2, third: 3);" ],
            'only closing parenthesis is on a new line' => [ "<?php\nunknown_call(first: 1, second: 2, third: 3\n);" ],
            'leading comment before expanded first argument' => [ "<?php\nunknown_call(/* Values */\n    first: 1, second: 2, third: 3);", true ],
        ];
    }

    public function test_literal_newlines_do_not_expand_an_otherwise_inline_argument_list(): void
    {
        $input = <<<'PHP'
        <?php
        unknown_call('first line
        second line');
        PHP;

        $this->assertSame($input, $this->fix($input));
    }

    #[DataProvider('provideCalls')]
    public function test_various_long_call_forms(string $input, string $expected): void
    {
        $input = "<?php\n" . $input . ";\n";
        $expected = "<?php\n" . $expected . ";\n";

        $this->assertSame($expected, $this->fix($input, 40));
        $this->assertSame($expected, $this->fix($expected, 40));
    }

    public static function provideCalls(): array
    {
        $first = '$firstLongValue';
        $second = '$secondLongValue';
        $cases = [];

        foreach ([ 'unknown_function', 'UnknownFactory::make', '$service->make', '$service?->make', '$factory', 'new UnknownData', '$callbacks[\'make\']' ] as $callable) {

            $cases[ $callable ] = [
                sprintf('%s(%s, %s)', $callable, $first, $second),
                sprintf("%s(\n    %s,\n    %s\n)", $callable, $first, $second),
            ];

        }

        $cases[ 'already named' ] = [
            'new UnknownData(first: $firstLongValue, second: $secondLongValue)',
            "new UnknownData(\n    first: \$firstLongValue,\n    second: \$secondLongValue\n)",
        ];

        $cases[ 'argument unpacking' ] = [
            'unknown_function($firstLongValue, ...$remainingValues)',
            "unknown_function(\n    \$firstLongValue,\n    ...\$remainingValues\n)",
        ];

        $cases[ 'nested arrays and callbacks' ] = [
            'unknown_function([ $firstLongValue, $secondLongValue ], fn ($first, $second) => $first)',
            "unknown_function(\n    [ \$firstLongValue, \$secondLongValue ],\n    fn (\$first, \$second) => \$first\n)",
        ];

        $cases[ 'punctuation inside strings' ] = [
            "unknown_function('commas, (parentheses), text', \$secondLongValue)",
            "unknown_function(\n    'commas, (parentheses), text',\n    \$secondLongValue\n)",
        ];

        $cases[ 'existing trailing comma' ] = [
            'unknown_function($firstLongValue, $secondLongValue,)',
            "unknown_function(\n    \$firstLongValue,\n    \$secondLongValue,\n)",
        ];

        return $cases;
    }

    public function test_calls_at_the_default_limit_stay_on_one_line(): void
    {
        $input = $this->callWithLineLength(140);

        $this->assertSame($input, $this->fix($input));
    }

    public function test_calls_above_the_default_limit_expand_without_guessing_names(): void
    {
        $input = $this->callWithLineLength(141);
        $expected = str_replace([ '(', ', ', ');' ], [ "(\n    ", ",\n    ", "\n);" ], $input);

        $this->assertSame($expected, $this->fix($input));
    }

    public function test_the_line_length_limit_is_configurable(): void
    {
        $input = $this->callWithLineLength(81);
        $expected = str_replace([ '(', ', ', ');' ], [ "(\n    ", ",\n    ", "\n);" ], $input);

        $this->assertSame($expected, $this->fix($input, 80));
    }

    public function test_a_higher_configured_limit_preserves_a_longer_call(): void
    {
        $input = $this->callWithLineLength(150);

        $this->assertSame($input, $this->fix($input, 160));
    }

    public function test_calls_between_120_and_140_characters_remain_inline(): void
    {
        $input = $this->callWithLineLength(130);

        $this->assertSame($input, $this->fix($input));
    }

    public function test_single_argument_callbacks_and_indivisible_values_are_preserved(): void
    {
        $input = <<<'PHP'
        <?php
        $collection->map(static fn (TicketMessage $message): TicketMessageData => $this->presentTicketMessageData($message));
        unknown_function('a long indivisible literal which does not become shorter by putting the argument on a separate line');
        PHP;

        $this->assertSame($input, $this->fix($input, 40));
    }

    public function test_resolved_function_arguments_are_named_after_wrapping(): void
    {
        $declaration = "<?php\nfunction combine(string \$first, string \$second): void {}\n";
        $input = $declaration . 'combine($firstLongValue, $secondLongValue);' . "\n";
        $expected = $declaration . "combine(\n    first: \$firstLongValue,\n    second: \$secondLongValue\n);\n";

        $this->assertSame($expected, $this->fix($input, 40));
        $this->assertSame($expected, $this->fix($expected, 40));
    }

    public function test_variadic_calls_are_wrapped_without_changing_argument_binding(): void
    {
        $declaration = "<?php\nfunction combine(string ...\$values): array { return \$values; }\n";
        $input = $declaration . 'combine($firstLongValue, $secondLongValue);' . "\n";
        $expected = $declaration . "combine(\n    \$firstLongValue,\n    \$secondLongValue\n);\n";

        $this->assertSame($expected, $this->fix($input, 40));
    }

    public function test_nested_calls_expand_when_added_argument_names_exceed_the_limit(): void
    {
        $declarations = <<<'PHP'
        <?php
        function outer(array $payloadWithAnEspeciallyLongName, array $other): void {}
        function inner(string $first, string $second): array { return []; }

        PHP;

        $input = $declarations . 'outer(inner($firstLongValue, $secondLongValue), $secondArgumentWithAnEspeciallyLongName);' . "\n";
        $expected = $declarations . <<<'PHP'
        outer(
            payloadWithAnEspeciallyLongName: inner(
                first: $firstLongValue,
                second: $secondLongValue
            ),
            other: $secondArgumentWithAnEspeciallyLongName
        );

        PHP;

        $this->assertSame($expected, $this->fix($input, 70));
        $this->assertSame($expected, $this->fix($expected, 70));
    }

    #[DataProvider('provideInvalidLimits')]
    public function test_invalid_line_length_limits_are_rejected(mixed $limit): void
    {
        $fixer = new MultilineNamedArgumentsFixer();

        $this->expectException(InvalidFixerConfigurationException::class);

        $fixer->configure([ 'max_line_length' => $limit ]);
    }

    public static function provideInvalidLimits(): array
    {
        return [ [ 0 ], [ -1 ], [ '120' ], [ 120.0 ] ];
    }

    public function test_first_class_callable_syntax_is_preserved(): void
    {
        $input = '<?php' . "\n" . str_repeat(' ', 120) . 'trim(...);' . "\n";

        $this->assertSame($input, $this->fix($input));
    }

    public function test_declarations_and_control_structure_parentheses_are_preserved(): void
    {
        $input = <<<'PHP'
        <?php

        function example_with_a_long_signature(string $firstParameterWithALongName, string $secondParameterWithALongName, string $thirdParameterWithALongName): void {}

        if ($firstConditionWithALongName && $secondConditionWithALongName && $thirdConditionWithALongName && $fourthConditionWithALongName) {}

        $callback = fn (string $firstParameterWithALongName, string $secondParameterWithALongName, string $thirdParameterWithALongName): string => '';

        PHP;

        $this->assertSame($input, $this->fix($input));
    }

    public function test_wrapping_respects_configured_indentation_and_line_endings(): void
    {
        $input = "<?php\r\n\r\n\tcall('first argument with a long value', 'second argument with a long value');\r\n";
        $expected = "<?php\r\n\r\n\tcall(\r\n\t\t'first argument with a long value',\r\n\t\t'second argument with a long value'\r\n\t);\r\n";

        $this->assertSame($expected, $this->fix($input, 60, new WhitespacesFixerConfig("\t", "\r\n")));
    }

    public function test_comments_and_nested_argument_expressions_are_preserved(): void
    {
        $input = <<<'PHP'
        <?php

        unknown_call(/* first value */ [ 'a', 'b' ], fn (string $value): string => $value, /* last value */ 'last');

        PHP;

        $expected = <<<'PHP'
        <?php

        unknown_call(
            /* first value */ [ 'a', 'b' ],
            fn (string $value): string => $value,
            /* last value */ 'last'
        );

        PHP;

        $this->assertSame($expected, $this->fix($input, 80));
        $this->assertSame($expected, $this->fix($expected, 80));
    }

    private function callWithLineLength(int $length): string
    {
        $call = "unknown_call('', 'value');";

        return sprintf("<?php\nunknown_call('%s', 'value');\n", str_repeat('x', $length - strlen($call)));
    }

    private function fix(string $input, int $maxLineLength = 140, ?WhitespacesFixerConfig $whitespaces = null): string
    {
        $tokens = Tokens::fromCode($input);
        $fixer = new MultilineNamedArgumentsFixer();
        $fixer->configure([ 'max_line_length' => $maxLineLength ]);

        if ($whitespaces !== null) {
            $fixer->setWhitespacesConfig($whitespaces);
        }

        $fixer->fix(new SplFileInfo('fixture.php'), $tokens);

        return $tokens->generateCode();
    }
}

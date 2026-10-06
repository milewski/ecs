<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Rules;

use Milewski\ECS\Fixers\MethodChainFixer;
use Milewski\ECS\Fixers\MultilineNamedArgumentsFixer;
use Milewski\ECS\Tests\Support\FixerTestCase;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use SplFileInfo;

final class SprintfArgumentLayoutTest extends FixerTestCase
{
    #[DataProvider('provideSprintfCalls')]
    public function test_sprintf_stays_inline_and_parent_arguments_expand(string $input, string $expected): void
    {
        $input = "<?php\n" . $input . "\n";
        $expected = "<?php\n" . $expected . "\n";

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public static function provideSprintfCalls(): array
    {
        $longFormat = str_repeat('a', 150) . '%s';

        return [
            'short free function wrapper' => [
                "\$name = trim(sprintf('%s %s', \$lead->first_name, \$lead->last_name)) ?: 'Unnamed lead';",
                "\$name = trim(sprintf('%s %s', \$lead->first_name, \$lead->last_name)) ?: 'Unnamed lead';",
            ],
            'previously expanded free function wrapper' => [
                "unknown_call(\n    name: trim(\n        sprintf('%s %s', \$lead->first_name, \$lead->last_name),\n    ) ?: 'Unnamed lead',\n);",
                "unknown_call(\n    name: trim(sprintf('%s %s', \$lead->first_name, \$lead->last_name)) ?: 'Unnamed lead',\n);",
            ],
            'qualified function wrapper' => [
                "Custom\\normalize(\n    \\sprintf('%s', \$value),\n);",
                "Custom\\normalize(\\sprintf('%s', \$value));",
            ],
            'explicitly named wrapper argument' => [
                "trim(\n    string: sprintf('%s', \$value),\n);",
                "trim(string: sprintf('%s', \$value));",
            ],
            'static method wrapper' => [
                "Formatter::normalize(sprintf('%s', \$value));",
                "Formatter::normalize(\n    sprintf('%s', \$value)\n);",
            ],
            'constructor wrapper' => [
                "new Label(sprintf('%s', \$value));",
                "new Label(\n    sprintf('%s', \$value)\n);",
            ],
            'dynamic function wrapper' => [
                "\$normalize(sprintf('%s', \$value));",
                "\$normalize(\n    sprintf('%s', \$value)\n);",
            ],
            'grouped sprintf is an expression' => [
                "\$label = (\n    sprintf('%s', \$value)\n);",
                "\$label = (\n    sprintf('%s', \$value)\n);",
            ],
            'short nested sprintf' => [
                '$limit->by(sprintf(\'crm-search:%s\', $userId));',
                "\$limit->by(\n    sprintf('crm-search:%s', \$userId)\n);",
            ],
            'expanded nested sprintf' => [
                "\$limit->by(sprintf(\n    'crm-search:%s',\n    \$request->user('crm')?->getAuthIdentifier(),\n));",
                "\$limit->by(\n    sprintf('crm-search:%s', \$request->user('crm')?->getAuthIdentifier())\n);",
            ],
            'fully qualified sprintf' => [
                '$limit->by(\sprintf(\'%s\', $userId));',
                "\$limit->by(\n    \\sprintf('%s', \$userId)\n);",
            ],
            'named parent argument' => [
                '$limit->by(key: sprintf(\'%s\', $userId));',
                "\$limit->by(\n    key: sprintf('%s', \$userId)\n);",
            ],
            'multiple parent arguments' => [
                'unknown_call(sprintf(\'%s\', $userId), $other);',
                "unknown_call(\n    sprintf('%s', \$userId),\n    \$other\n);",
            ],
            'standalone expanded sprintf' => [
                "\$label = sprintf(\n    '%s:%s',\n    \$first,\n    \$second,\n);",
                '$label = sprintf(\'%s:%s\', $first, $second);',
            ],
            'long standalone sprintf' => [
                "sprintf('$longFormat', \$value);",
                "sprintf('$longFormat', \$value);",
            ],
            'long nested sprintf' => [
                "\$limit->by(sprintf('$longFormat', \$value));",
                "\$limit->by(\n    sprintf('$longFormat', \$value)\n);",
            ],
            'nested method chains remain compact inside sprintf' => [
                "sprintf('%s', \$repository->findMatchingRecords(\$firstLongValue, \$secondLongValue)->first()->name());",
                "sprintf('%s', \$repository->findMatchingRecords(\$firstLongValue, \$secondLongValue)->first()->name());",
            ],
        ];
    }

    #[DataProvider('provideWrapperLengths')]
    public function test_function_wrapper_length_includes_prefix_and_suffix(int $maximumLength, bool $fits): void
    {
        $compact = "<?php\n    \$label = trim(sprintf('%s', \$value)) ?: 'fallback';";
        $expanded = "<?php\n    \$label = trim(\n        string: sprintf('%s', \$value)\n    ) ?: 'fallback';";
        $expected = $fits ? $compact : $expanded;
        $previouslyExpanded = "<?php\n    \$label = trim(\n        sprintf('%s', \$value),\n    ) ?: 'fallback';";

        $this->assertSame($expected, $this->fix($compact, $maximumLength));
        $this->assertSame(
            expected: $fits ? $compact : str_replace("\$value)\n", "\$value),\n", $expanded),
            actual: $this->fix($previouslyExpanded, $maximumLength),
        );

        $this->assertSame($expected, $this->fix($expected, $maximumLength));
    }

    public static function provideWrapperLengths(): array
    {
        $length = strlen("    \$label = trim(sprintf('%s', \$value)) ?: 'fallback';");

        return [
            'below limit' => [ $length + 1, true ],
            'at limit' => [ $length, true ],
            'above limit' => [ $length - 1, false ],
        ];
    }

    public function test_wrapper_compaction_uses_the_expanded_parent_layout_in_the_first_pass(): void
    {
        $input = "<?php\nunknown_call(name: trim(\n    sprintf('%s', \$value),\n) ?: 'fallback', other: sprintf('%s', \$anotherValue));";
        $expected = "<?php\nunknown_call(\n    name: trim(sprintf('%s', \$value)) ?: 'fallback',\n    other: sprintf('%s', \$anotherValue)\n);";

        $this->assertSame($expected, $this->fix($input, 60));
        $this->assertSame($expected, $this->fix($expected, 60));
    }

    #[DataProvider('provideUnsafeWrappers')]
    public function test_wrapper_comments_and_multiline_literals_are_preserved(string $input): void
    {
        $input = "<?php\n" . $input;

        $this->assertSame($input, $this->fix($input));
        $this->assertSame($input, $this->fix($this->fix($input)));
    }

    public static function provideUnsafeWrappers(): array
    {
        return [
            'comment' => [ "unknown_wrapper(\n    // Explain the label.\n    sprintf('%s', \$value)\n);" ],
            'multiline string' => [ "unknown_wrapper(\n    sprintf('first line\nsecond line %s', \$value)\n);" ],
            'closure body' => [ "unknown_wrapper(\n    sprintf('%s', static function () {\n        return 'value';\n    })\n);" ],
        ];
    }

    public function test_comments_and_literal_newlines_are_preserved(): void
    {
        $input = <<<'PHP'
        <?php
        sprintf(
            '%s', // Keep the reason for this value.
            $value,
        );
        sprintf('first line
        second line %s', $value);
        PHP;

        $this->assertSame($input, $this->fix($input));
    }

    public function test_methods_named_sprintf_are_formatted_normally(): void
    {
        $input = '<?php' . "\n" . '$formatter->sprintf($firstLongValue, $secondLongValue);';
        $expected = '<?php' . "\n\$formatter->sprintf(\n    \$firstLongValue,\n    \$secondLongValue\n);";

        $this->assertSame($expected, $this->fix($input, 40));
    }

    #[DataProvider('provideOtherSprintfCalls')]
    public function test_other_callables_named_sprintf_are_formatted_normally(string $call): void
    {
        $input = '<?php' . "\n" . $call . '($firstLongValue, $secondLongValue);';
        $expected = '<?php' . "\n" . $call . "(\n    \$firstLongValue,\n    \$secondLongValue\n);";

        $this->assertSame($expected, $this->fix($input, 40));
    }

    public static function provideOtherSprintfCalls(): array
    {
        return [
            'namespaced function' => [ 'Custom\\sprintf' ],
            'fully qualified namespaced function' => [ '\\Custom\\sprintf' ],
            'static method' => [ 'Formatter::sprintf' ],
            'nullsafe method' => [ '$formatter?->sprintf' ],
        ];
    }

    public function test_sprintf_layout_respects_tabs_and_crlf(): void
    {
        $input = "<?php\r\n\t\$limit->by(sprintf(\r\n\t\t'%s',\r\n\t\t\$userId,\r\n\t));\r\n";
        $expected = "<?php\r\n\t\$limit->by(\r\n\t\tsprintf('%s', \$userId)\r\n\t);\r\n";
        $tokens = Tokens::fromCode($input);

        foreach ([ new MethodChainFixer(), new MultilineNamedArgumentsFixer() ] as $fixer) {

            $fixer->configure([]);
            $fixer->setWhitespacesConfig(new WhitespacesFixerConfig("\t", "\r\n"));
            $fixer->fix(new SplFileInfo('fixture.php'), $tokens);

        }

        $this->assertSame($expected, $tokens->generateCode());
    }

    private function fix(string $input, ?int $limit = null): string
    {
        $tokens = Tokens::fromCode($input);

        foreach ([ new MethodChainFixer(), new MultilineNamedArgumentsFixer() ] as $fixer) {

            $fixer->configure([ 'max_line_length' => $limit ?? ($fixer instanceof MethodChainFixer ? 120 : 140) ]);
            $fixer->fix(new SplFileInfo('fixture.php'), $tokens);

        }

        return $tokens->generateCode();
    }
}

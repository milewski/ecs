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

<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Rules;

use Milewski\ECS\Fixers\MethodChainFixer;
use Milewski\ECS\Fixers\MultilineNamedArgumentsFixer;
use Milewski\ECS\Tests\Support\FixerTestCase;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\Attributes\DataProvider;
use SplFileInfo;

final class BooleanExpressionWrappingTest extends FixerTestCase
{
    #[DataProvider('provideOperators')]
    public function test_boolean_and_comparison_operands_stay_inline(string $expression): void
    {
        $input = "<?php\n\$result = " . $expression . ";\n";

        $this->assertSame($input, $this->fix($input, 40));
        $this->assertSame($input, $this->fix($this->fix($input, 40), 40));
    }

    public static function provideOperators(): array
    {
        $cases = [];

        foreach ([ '||', '&&', 'or', 'and', 'xor', '>', '<', '>=', '<=', '===', '!==', '==', '!=', '<>', '<=>' ] as $operator) {

            $cases[ $operator . ' with calls on both sides' ] = [
                sprintf('matches($firstLongValue, $secondLongValue) %s matches($thirdLongValue, $fourthLongValue)', $operator),
            ];

            $cases[ $operator . ' with a method chain' ] = [
                sprintf('$query->where($firstLongValue, $secondLongValue)->exists() %s $expectedResult', $operator),
            ];

        }

        $cases[ 'instanceof' ] = [ 'factory($firstLongValue, $secondLongValue) instanceof Result' ];
        $cases[ 'logical negation' ] = [ '!matches($firstLongValue, $secondLongValue)' ];

        return $cases;
    }

    #[DataProvider('provideExpressionContexts')]
    public function test_expression_exemptions_follow_nested_operands(string $input): void
    {
        $input = "<?php\n" . $input . "\n";

        $this->assertSame($input, $this->fix($input, 40));
    }

    public static function provideExpressionContexts(): array
    {
        $expression = 'matches($firstLongValue, $secondLongValue) || matches($thirdLongValue, $fourthLongValue)';

        return [
            'return' => [ 'return ' . $expression . ';' ],
            'nested grouping' => [ '$result = ((' . $expression . '));' ],
            'array value' => [ '$results = [ \'allowed\' => ' . $expression . ' ];' ],
            'arrow callback' => [ '$callback = static fn (): bool => ' . $expression . ';' ],
            'ternary condition' => [ '$result = ' . $expression . ' ? 1 : 0;' ],
            'nested calls inside operands' => [ '$result = check(matches($firstLongValue, $secondLongValue)) === true;' ],
            'named argument' => [ "abort_unless(\n    boolean: " . $expression . ",\n    code: 404\n);" ],
            'positional argument' => [ "abort_unless(\n    " . $expression . ",\n    404\n);" ],
        ];
    }

    public function test_the_reported_abort_unless_expression_stays_inline(): void
    {
        $input = <<<'PHP'
        <?php
        abort_unless(
            boolean: in_array('crm.customers.browse', $context->permissions, true) || in_array($leadId, $this->disclosedLeadIds($user), true),
            code: 404,
            message: 'Customer not found.',
        );
        PHP;

        $this->assertSame($input, $this->fix($input));
        $this->assertSame($input, $this->fix($this->fix($input)));
    }

    public function test_outer_and_sibling_calls_still_wrap(): void
    {
        $input = <<<'PHP'
        <?php
        unknown_call(matches($firstLongValue, $secondLongValue) > 0, other_call($thirdLongValue, $fourthLongValue));
        PHP;

        $expected = <<<'PHP'
        <?php
        unknown_call(
            matches($firstLongValue, $secondLongValue) > 0,
            other_call(
                $thirdLongValue,
                $fourthLongValue
            )
        );
        PHP;

        $this->assertSame($expected, $this->fix($input, 40));
        $this->assertSame($expected, $this->fix($expected, 40));
    }

    public function test_previously_expanded_comparison_calls_are_repaired(): void
    {
        $input = <<<'PHP'
        <?php
        abort_unless(
            boolean: in_array(
                needle: 'crm.customers.browse',
                haystack: $context->permissions,
                strict: true,
            ) || in_array($leadId, $this->disclosedLeadIds($user), true),
            code: 404,
            message: 'Customer not found.',
        );
        PHP;

        $expected = <<<'PHP'
        <?php
        abort_unless(
            boolean: in_array('crm.customers.browse', $context->permissions, true) || in_array($leadId, $this->disclosedLeadIds($user), true),
            code: 404,
            message: 'Customer not found.',
        );
        PHP;

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    #[DataProvider('provideNamedOperands')]
    public function test_compacting_preserves_parameter_binding(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public static function provideNamedOperands(): array
    {
        return [
            'reordered named parameters' => [
                "<?php\n\$allowed = in_array(\n    haystack: \$permissions,\n    needle: \$permission,\n    strict: true,\n) === true;",
                '<?php' . "\n" . '$allowed = in_array(haystack: $permissions, needle: $permission, strict: true) === true;',
            ],
            'skipped optional parameter' => [
                "<?php\nfunction matches(\$first = null, \$second = null): bool { return true; }\n\$allowed = matches(\n    second: \$value,\n) === true;",
                "<?php\nfunction matches(\$first = null, \$second = null): bool { return true; }\n\$allowed = matches(second: \$value) === true;",
            ],
            'unknown named parameters' => [
                "<?php\n\$allowed = unknown_call(\n    option: \$value,\n) === true;",
                '<?php' . "\n" . '$allowed = unknown_call(option: $value) === true;',
            ],
            'inline explicit names' => [
                '<?php' . "\n" . '$allowed = in_array(needle: $value, haystack: $items, strict: true) === true;',
                '<?php' . "\n" . '$allowed = in_array(needle: $value, haystack: $items, strict: true) === true;',
            ],
            'named variadic key' => [
                "<?php\nfunction matches(...\$values): bool { return true; }\n\$allowed = matches(\n    values: \$value,\n) === true;",
                "<?php\nfunction matches(...\$values): bool { return true; }\n\$allowed = matches(values: \$value) === true;",
            ],
            'comment inside operand' => [
                "<?php\n\$allowed = in_array(\n    \$value, // Keep this comment.\n    \$items,\n) === true;",
                "<?php\n\$allowed = in_array(\n    needle: \$value, // Keep this comment.\n    haystack: \$items,\n) === true;",
            ],
        ];
    }

    public function test_operator_text_inside_strings_does_not_disable_wrapping(): void
    {
        $input = '<?php' . "\n" . 'unknown_call(\'sql with > < === || && symbols\', $secondLongValue);';
        $expected = '<?php' . "\nunknown_call(\n    'sql with > < === || && symbols',\n    \$secondLongValue\n);";

        $this->assertSame($expected, $this->fix($input, 40));
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

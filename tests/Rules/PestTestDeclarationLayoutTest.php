<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Rules;

use Milewski\ECS\Fixers\MultilineNamedArgumentsFixer;
use Milewski\ECS\Tests\Support\FixerTestCase;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use SplFileInfo;

final class PestTestDeclarationLayoutTest extends FixerTestCase
{
    #[DataProvider('provideTestDeclarations')]
    public function test_pest_declarations_keep_positional_arguments_inline(string $input, string $expected): void
    {
        $input = "<?php\n" . $input;
        $expected = "<?php\n" . $expected;

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public static function provideTestDeclarations(): array
    {
        $description = "'verification cannot turn an uncertain or still-payable intent into a failed purchase'";
        $callback = "function (string \$status) use (\$actingAsLead, \$mockStripeWithStatus): void {\n\n    \$this->assertSame('pending', \$status);\n\n}";

        return [
            'resolvable test parameter names remain positional' => [
                "function test(string \$description, ?Closure \$closure = null): void {}\ntest($description, $callback);",
                "function test(string \$description, ?Closure \$closure = null): void {}\ntest($description, $callback);",
            ],
            'long inline callback with dataset' => [
                "test($description, $callback)->with([ 'unknown', 'requires_payment_method' ]);",
                "test($description, $callback)->with([ 'unknown', 'requires_payment_method' ]);",
            ],
            'expanded positional arguments' => [
                "test(\n    $description,\n    $callback,\n)->with([ 'unknown', 'requires_payment_method' ]);",
                "test($description, $callback)->with([ 'unknown', 'requires_payment_method' ]);",
            ],
            'previously added argument names' => [
                "test(\n    description: $description,\n    closure: $callback,\n);",
                "test($description, $callback);",
            ],
            'mixed positional and named arguments' => [
                "test(\n    $description,\n    closure: $callback,\n);",
                "test($description, $callback);",
            ],
            'inline named arguments' => [
                "test(description: 'short test', closure: static fn (): bool => true);",
                "test('short test', static fn (): bool => true);",
            ],
            'fully qualified global function' => [
                "\\test(\n    description: $description,\n    closure: $callback,\n);",
                "\\test($description, $callback);",
            ],
            'case insensitive global function' => [
                "TEST(\n    description: 'short test',\n    closure: \$callback,\n);",
                "TEST('short test', \$callback);",
            ],
            'higher order test without a callback' => [
                "test(\n    description: $description,\n)->assertTrue(true);",
                "test($description)->assertTrue(true);",
            ],
            'test helper without arguments' => [ "test(\n);", 'test();' ],
            'first class callable' => [ "\$callback = test(\n    ...\n);", '$callback = test(...);' ],
            'unpacked arguments keep their binding' => [ "test(\n    ...\$arguments,\n);", 'test(...$arguments);' ],
            'out of order named arguments retain their binding' => [
                "test(\n    closure: \$callback,\n    description: 'short test',\n);",
                "test(closure: \$callback, description: 'short test');",
            ],
            'callback supplied by name without a description' => [
                "test(\n    closure: \$callback,\n);",
                'test(closure: $callback);',
            ],
            'literal newlines in descriptions are preserved' => [
                "test(\n    'first line\nsecond line',\n    \$callback,\n);",
                "test('first line\nsecond line', \$callback);",
            ],
        ];
    }

    public function test_callback_bodies_are_formatted_normally(): void
    {
        $input = <<<'PHP'
        <?php
        test('the callback body keeps the normal argument rules', function (): void {
            trim(
                $value,
            );
        });
        PHP;

        $expected = <<<'PHP'
        <?php
        test('the callback body keeps the normal argument rules', function (): void {
            trim(
                string: $value,
            );
        });
        PHP;

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    #[DataProvider('provideOtherTestCallables')]
    public function test_other_callables_named_test_follow_the_normal_call_rules(string $callable): void
    {
        $input = "<?php\n" . $callable . "(\n    description: 'test',\n    closure: \$callback,\n);";

        $this->assertSame($input, $this->fix($input));
    }

    public static function provideOtherTestCallables(): array
    {
        return [
            'method' => [ '$runner->test' ],
            'nullsafe method' => [ '$runner?->test' ],
            'static method' => [ 'Runner::test' ],
            'constructor' => [ 'new Test' ],
            'qualified function' => [ 'Custom\\test' ],
            'fully qualified custom function' => [ '\\Custom\\test' ],
            'relative namespaced function' => [ 'namespace\\test' ],
            'dynamic function' => [ '$test' ],
        ];
    }

    public function test_attributes_named_test_are_not_compacted(): void
    {
        $input = "<?php\n#[Test(\n    description: 'test',\n    closure: null,\n)]\nfunction example(): void {}";

        $this->assertSame($input, $this->fix($input));
    }

    public function test_function_declarations_named_test_are_not_compacted(): void
    {
        $input = "<?php\nfunction &test(\n    string \$description,\n    ?Closure \$closure = null,\n): mixed {}";

        $this->assertSame($input, $this->fix($input));

        $input = str_replace('function &test', 'function test', $input);

        $this->assertSame($input, $this->fix($input));
    }

    public function test_removing_argument_names_preserves_comments_around_the_names_and_values(): void
    {
        $input = "<?php\ntest(description /* Explain the name. */: /* Explain the value. */ 'test', closure: \$callback);";
        $expected = "<?php\ntest( /* Explain the name. */ /* Explain the value. */ 'test', \$callback);";

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public function test_comments_between_arguments_are_preserved(): void
    {
        $input = <<<'PHP'
        <?php
        test(
            // Explain this description.
            'commented test',
            // Explain the callback.
            $callback
            // Preserve the closing comment.
        );
        PHP;

        $this->assertSame($input, $this->fix($input));
        $this->assertSame($input, $this->fix($this->fix($input)));
    }

    public function test_tabs_and_crlf_are_preserved_inside_the_callback(): void
    {
        $input = "<?php\r\n\ttest(\r\n\t\tdescription: 'tabbed test',\r\n\t\tclosure: function (): void {\r\n\r\n\t\t\t\$this->assertTrue(true);\r\n\r\n\t\t},\r\n\t);\r\n";
        $expected = "<?php\r\n\ttest('tabbed test', function (): void {\r\n\r\n\t\t\t\$this->assertTrue(true);\r\n\r\n\t\t});\r\n";
        $whitespaces = new WhitespacesFixerConfig("\t", "\r\n");

        $this->assertSame($expected, $this->fix($input, $whitespaces));
        $this->assertSame($expected, $this->fix($expected, $whitespaces));
    }

    private function fix(string $input, ?WhitespacesFixerConfig $whitespaces = null): string
    {
        $tokens = Tokens::fromCode($input);
        $fixer = new MultilineNamedArgumentsFixer();
        $fixer->configure([ 'max_line_length' => 140 ]);

        if ($whitespaces !== null) {
            $fixer->setWhitespacesConfig($whitespaces);
        }

        $fixer->fix(new SplFileInfo('fixture.php'), $tokens);

        return $tokens->generateCode();
    }
}

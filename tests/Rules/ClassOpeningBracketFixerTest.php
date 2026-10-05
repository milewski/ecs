<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Rules;

use Milewski\ECS\Fixers\ClassOpeningBracketFixer;
use Milewski\ECS\Tests\Support\FixerTestCase;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use SplFileInfo;

final class ClassOpeningBracketFixerTest extends FixerTestCase
{
    #[DataProvider('provideDeclarations')]
    public function test_class_opening_braces_use_the_next_line(string $declaration): void
    {
        $input = "<?php\n" . $declaration . '{};';
        $expected = "<?php\n" . rtrim($declaration) . "\n{};";

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public static function provideDeclarations(): array
    {
        return [
            'anonymous class with inheritance' => [ 'return new class () extends Operation ' ],
            'anonymous class without parent or whitespace' => [ 'return new class' ],
            'anonymous class with interfaces' => [ '$operation = new class () extends Operation implements Runnable, Queueable ' ],
            'readonly anonymous class' => [ 'return new readonly class () ' ],
            'anonymous class with an attribute' => [ 'return new #[Marker] class () extends Operation ' ],
            'comment before the body' => [ 'return new class () extends Operation /* Keep this comment. */' ],
            'named class' => [ 'final class Operation ' ],
            'interface' => [ 'interface Runnable ' ],
            'trait' => [ 'trait Queueable ' ],
        ];
    }

    public function test_constructor_argument_braces_are_not_treated_as_the_class_body(): void
    {
        $input = <<<'PHP'
        <?php
        return new class (static function () { return 1; }, new class {}) extends Operation {
            public function run(): void {}
        };
        PHP;

        $expected = <<<'PHP'
        <?php
        return new class (static function () { return 1; }, new class
        {}) extends Operation
        {
            public function run(): void {}
        };
        PHP;

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public function test_nested_anonymous_classes_and_class_constants_are_preserved(): void
    {
        $input = <<<'PHP'
        <?php
        class Factory {
            public function make(): object
            {
                $name = Operation::class;

                return new class () extends Operation {
                    public function make(): object
                    {
                        return new class {};
                    }
                };
            }
        }
        PHP;

        $expected = <<<'PHP'
        <?php
        class Factory
        {
            public function make(): object
            {
                $name = Operation::class;

                return new class () extends Operation
                {
                    public function make(): object
                    {
                        return new class
                        {};
                    }
                };
            }
        }
        PHP;

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public function test_class_constants_without_declarations_are_unchanged(): void
    {
        $input = '<?php $name = Operation::class;';

        $this->assertSame($input, $this->fix($input));
    }

    public function test_blank_lines_at_body_boundaries_are_removed_without_changing_comments_or_members(): void
    {
        $input = <<<'PHP'
        <?php
        return new class () extends Operation {

            // Keep this comment.
            public string $name;

            public string $description;

        };
        PHP;

        $expected = <<<'PHP'
        <?php
        return new class () extends Operation
        {
            // Keep this comment.
            public string $name;

            public string $description;
        };
        PHP;

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public function test_configured_indentation_and_line_endings_are_used(): void
    {
        $input = "<?php\r\n\treturn new class () extends Operation {\r\n\r\n\t\tpublic string \$name;\r\n\r\n\t};\r\n";
        $expected = "<?php\r\n\treturn new class () extends Operation\r\n\t{\r\n\t\tpublic string \$name;\r\n\t};\r\n";
        $whitespaces = new WhitespacesFixerConfig("\t", "\r\n");

        $this->assertSame($expected, $this->fix($input, $whitespaces));
        $this->assertSame($expected, $this->fix($expected, $whitespaces));
    }

    private function fix(string $input, ?WhitespacesFixerConfig $whitespaces = null): string
    {
        $tokens = Tokens::fromCode($input);
        $fixer = new ClassOpeningBracketFixer();

        if ($whitespaces !== null) {
            $fixer->setWhitespacesConfig($whitespaces);
        }

        $fixer->fix(new SplFileInfo('fixture.php'), $tokens);

        return $tokens->generateCode();
    }
}

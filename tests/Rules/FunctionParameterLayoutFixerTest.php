<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Rules;

use Milewski\ECS\Fixers\FunctionParameterLayoutFixer;
use Milewski\ECS\Tests\Support\EcsTestCase;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;
use SplFileInfo;

final class FunctionParameterLayoutFixerTest extends EcsTestCase
{
    public function provideConfig(): string
    {
        return __DIR__ . '/../Support/FunctionParameterLayout.php';
    }

    public function test_places_constructor_parameter_attributes_on_separate_lines(): void
    {
        $this->assertFixtureIsFixedTo(
            inputFixture: __DIR__ . '/../Fixtures/FunctionParameterLayout/Before/CrmLoginData.php',
            expectedFixture: __DIR__ . '/../Fixtures/FunctionParameterLayout/After/CrmLoginData.php',
        );
    }

    public function test_preserves_constructor_parameter_attribute_lines(): void
    {
        $this->assertFixturePasses(
            fixture: __DIR__ . '/../Fixtures/FunctionParameterLayout/After/CrmLoginData.php',
        );
    }

    public function test_expands_stacked_attributes_without_changing_nested_attribute_arguments(): void
    {
        $this->assertCodeIsFixedTo(
            input: <<<'PHP'
<?php

final class AttributedData
{
    public function __construct(#[Min(1)]#[Max(255)]public readonly string $username, #[Options(['first', 'second'], new Rule(1, 2))] string $password)
    {
        $this->validate($username, $password);
    }
}
PHP,
            expected: <<<'PHP'
<?php

final class AttributedData
{
    public function __construct(
        #[Min(1)]
        #[Max(255)]
        public readonly string $username,
        #[Options(['first', 'second'], new Rule(1, 2))]
        string $password,
    )
    {
        $this->validate($username, $password);
    }
}
PHP,
        );
    }

    public function test_attribute_lines_use_configured_indentation_and_line_endings(): void
    {
        $input = "<?php\r\n\r\nclass AttributedData\r\n{\r\n\tpublic function __construct(#[Min(1)]public readonly string \$username)\r\n\t{\r\n\t}\r\n}\r\n";
        $expected = "<?php\r\n\r\nclass AttributedData\r\n{\r\n\tpublic function __construct(\r\n\t\t#[Min(1)]\r\n\t\tpublic readonly string \$username,\r\n\t)\r\n\t{\r\n\t}\r\n}\r\n";
        $tokens = Tokens::fromCode($input);
        $fixer = new FunctionParameterLayoutFixer();
        $fixer->setWhitespacesConfig(new WhitespacesFixerConfig("\t", "\r\n"));
        $fixer->fix(new SplFileInfo('AttributedData.php'), $tokens);

        $this->assertSame($expected, $tokens->generateCode());
    }

    public function test_keeps_a_single_attributed_parameter_expanded_with_a_non_empty_body(): void
    {
        $this->assertCodeIsFixedTo(
            input: <<<'PHP'
<?php

final class AttributedData
{
    public function __construct(#[Min(1)] public readonly string $username)
    {
        $this->validate();
    }
}
PHP,
            expected: <<<'PHP'
<?php

final class AttributedData
{
    public function __construct(
        #[Min(1)]
        public readonly string $username,
    )
    {
        $this->validate();
    }
}
PHP,
        );
    }

    public function test_expands_constructors_and_compacts_short_functions(): void
    {
        $this->assertFixtureIsFixedTo(
            inputFixture: __DIR__ . '/../Fixtures/FunctionParameterLayout/unfixed.php',
            expectedFixture: __DIR__ . '/../Fixtures/FunctionParameterLayout/fixed.php',
        );
    }

    public function test_compacts_simple_body_constructors_including_promoted_properties(): void
    {
        $this->assertFixtureIsFixedTo(
            inputFixture: __DIR__ . '/../Fixtures/FunctionParameterLayout/body_constructor_unfixed.php',
            expectedFixture: __DIR__ . '/../Fixtures/FunctionParameterLayout/body_constructor_fixed.php',
        );
    }

    public function test_expands_body_constructors_with_multiple_parameters(): void
    {
        $this->assertFixtureIsFixedTo(
            inputFixture: __DIR__ . '/../Fixtures/FunctionParameterLayout/multi_parameter_body_constructor_unfixed.php',
            expectedFixture: __DIR__ . '/../Fixtures/FunctionParameterLayout/multi_parameter_body_constructor_fixed.php',
        );
    }

    public function test_places_the_opening_brace_after_a_compacted_signature(): void
    {
        $this->assertFixtureIsFixedTo(
            inputFixture: __DIR__ . '/../Fixtures/FunctionParameterLayout/opening_brace_unfixed.php',
            expectedFixture: __DIR__ . '/../Fixtures/FunctionParameterLayout/opening_brace_fixed.php',
        );
    }

    public function test_expands_compact_named_function_bodies(): void
    {
        $this->assertFixtureIsFixedTo(
            inputFixture: __DIR__ . '/../Fixtures/FunctionParameterLayout/compact_body_unfixed.php',
            expectedFixture: __DIR__ . '/../Fixtures/FunctionParameterLayout/compact_body_fixed.php',
        );
    }

    public function test_expanded_named_function_bodies_are_idempotent(): void
    {
        $this->assertFixturePasses(
            fixture: __DIR__ . '/../Fixtures/FunctionParameterLayout/compact_body_fixed.php',
        );
    }

    public function test_body_expansion_uses_configured_indentation_and_line_endings(): void
    {
        $input = "<?php\r\n\r\nclass TicketOperations\r\n{\r\n\tpublic function touch(Ticket \$ticket): void {\$ticket->touch();}\r\n}\r\n";
        $expected = "<?php\r\n\r\nclass TicketOperations\r\n{\r\n\tpublic function touch(Ticket \$ticket): void\r\n\t{\r\n\t\t\$ticket->touch();\r\n\t}\r\n}\r\n";
        $tokens = Tokens::fromCode($input);
        $fixer = new FunctionParameterLayoutFixer();
        $fixer->setWhitespacesConfig(new WhitespacesFixerConfig("\t", "\r\n"));
        $fixer->fix(new SplFileInfo('TicketOperations.php'), $tokens);

        $this->assertSame($expected, $tokens->generateCode());
    }

    public function test_compacts_functions_regardless_of_parameter_count(): void
    {
        $this->assertFixtureIsFixedTo(
            inputFixture: __DIR__ . '/../Fixtures/FunctionParameterLayout/unlimited_parameters_unfixed.php',
            expectedFixture: __DIR__ . '/../Fixtures/FunctionParameterLayout/unlimited_parameters_fixed.php',
        );
    }

    public function test_keeps_complex_parameters_expanded(): void
    {
        $this->assertFixturePasses(
            fixture: __DIR__ . '/../Fixtures/FunctionParameterLayout/complex_parameters.php',
        );
    }

    public function test_compacts_overlong_signatures_with_up_to_six_parameters(): void
    {
        $this->assertFixtureIsFixedTo(
            inputFixture: __DIR__ . '/../Fixtures/FunctionParameterLayout/long_signature.php',
            expectedFixture: __DIR__ . '/../Fixtures/FunctionParameterLayout/long_signature_fixed.php',
        );
    }
}

<?php

declare(strict_types = 1);

namespace DigitalCreative\ECS\Tests\Rules;

use DigitalCreative\ECS\Fixers\FunctionParameterLayoutFixer;
use DigitalCreative\ECS\Tests\Support\EcsTestCase;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;
use SplFileInfo;

final class FunctionParameterLayoutFixerTest extends EcsTestCase
{
    public function provideConfig(): string
    {
        return __DIR__ . '/../Support/FunctionParameterLayout.php';
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

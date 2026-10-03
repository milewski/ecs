<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests;

use Milewski\ECS\Tests\Support\EcsTestCase;

final class SnakeCaseGlobalFunctionNameFixerTest extends EcsTestCase
{
    public function test_call_wrapping_uses_the_final_function_name_length(): void
    {
        $declaration = "<?php\n\ndeclare(strict_types = 1);\n\nfunction combineTicketInformation(string \$first, string \$second): void\n{\n}\n\n";
        $call = "combineTicketInformation('', 'value');";
        $value = str_repeat('x', 119 - strlen($call));
        $input = $declaration . sprintf("combineTicketInformation('%s', 'value');\n", $value);
        $expected = str_replace('combineTicketInformation', 'combine_ticket_information', $declaration)
            . sprintf("combine_ticket_information(\n    first: '%s',\n    second: 'value',\n);\n", $value);

        $this->assertCodeIsFixedTo($input, $expected);
        $this->assertCodeIsFixedTo($expected, $expected);
    }

    public function test_global_function_declarations_and_direct_references_use_snake_case(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/SnakeCaseGlobalFunctionNameFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/GlobalFunctions.php',
            expectedFixture: $fixtureDirectory . '/After/GlobalFunctions.php',
        );
    }

    public function test_snake_case_global_functions_are_idempotent(): void
    {
        $this->assertFixturePasses(
            __DIR__ . '/Fixtures/SnakeCaseGlobalFunctionNameFixer/After/GlobalFunctions.php',
        );
    }
}

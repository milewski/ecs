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

    public function test_short_calls_inside_an_arrow_callback_stay_inline(): void
    {
        $input = <<<'PHP'
        <?php
                $missed = Order::withoutEvents(fn (): Order => OrderFactory::new()->withProduct($this->product, 2)->create([ 'status' => OrderStatus::Shipped->value ]));
        PHP;

        $this->assertSame($input, $this->fix($input));
    }

    public function test_imported_trait_methods_use_their_declared_parameter_names(): void
    {
        $input = <<<'PHP'
        <?php
        use Milewski\ECS\Tests\Support\ReflectionPestAssertions;
        class SupplierToolsTest {
            use ReflectionPestAssertions;
            public function run(): void {
                $this->assertResourceAccess(
                    $resource,
                    'read',
                );
            }
        }
        PHP;

        $expected = str_replace(
            [ '$resource,', "'read'," ],
            [ 'resource: $resource,', "ability: 'read'," ],
            $input,
        );

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
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

    public function test_short_nested_calls_stay_inline_after_the_outer_call_is_named(): void
    {
        $declarations = <<<'PHP'
        <?php
        function outer(array $payloadWithAnEspeciallyLongName, array $other): void {}
        function inner(string $first, string $second): array { return []; }

        PHP;

        $input = $declarations . 'outer(inner($firstLongValue, $secondLongValue), $secondArgumentWithAnEspeciallyLongName);' . "\n";
        $expected = $declarations . <<<'PHP'
        outer(
            payloadWithAnEspeciallyLongName: inner($firstLongValue, $secondLongValue),
            other: $secondArgumentWithAnEspeciallyLongName
        );

        PHP;

        $this->assertSame($expected, $this->fix($input, 70));
        $this->assertSame($expected, $this->fix($expected, 70));
    }

    public function test_inherited_mixins_resolve_imported_aliases(): void
    {
        $input = <<<'PHP'
        <?php
        use Milewski\ECS\Tests\Support\ReflectionMcpServer;
        final class MetaAdsServer extends ReflectionMcpServer {}
        MetaAdsServer::tool(
            app(MetaAdsEndpointCatalog::class)->tool('meta-ads-campaigns-write'),
            [ 'operation' => 'campaigns-update', 'confirm' => true ],
        )
            ->assertHasErrors()
            ->assertSee('mutation target');
        PHP;

        $expected = str_replace(
            [ "    app(", "    [ 'operation'" ],
            [ "    tool: app(", "    arguments: [ 'operation'" ],
            $input,
        );

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public function test_inherited_mixin_return_types_resolve_later_fluent_calls(): void
    {
        $input = <<<'PHP'
        <?php
        use Milewski\ECS\Tests\Support\ReflectionMcpServer;
        final class MetaAdsServer extends ReflectionMcpServer {}
        MetaAdsServer::tool('first', [])
            ->tool(
                'second',
                [],
            );
        PHP;

        $expected = str_replace(
            [ "        'second',", '        [],' ],
            [ "        tool: 'second',", '        arguments: [],' ],
            $input,
        );

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    #[DataProvider('provideTraitMethodDeclarations')]
    public function test_trait_method_resolution_preserves_precedence_and_uncertainty(string $declarations, ?string $parameter): void
    {
        $call = "\$this->handle(\n            \$resource,\n            'read',\n        );";
        $input = "<?php\n" . str_replace('__CALL__', $call, $declarations);
        $expected = $input;

        if ($parameter !== null) {

            $namedCall = str_replace([ '$resource,', "'read'," ], [ $parameter . ': $resource,', "ability: 'read'," ], $call);
            $expected = "<?php\n" . str_replace('__CALL__', $namedCall, $declarations);

        }

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public static function provideTraitMethodDeclarations(): array
    {
        $handler = 'trait Handler { public function handle(object $resource, string $ability): void {} }';
        $parent = 'class BaseHandler { public function handle(object $parentResource, string $ability): void {} }';
        $run = 'public function run(): void { __CALL__ }';

        return [
            'local trait' => [ $handler . ' class Example { use Handler; ' . $run . ' }', 'resource' ],
            'nested trait' => [ $handler . ' trait NestedHandler { use Handler; } class Example { use NestedHandler; ' . $run . ' }', 'resource' ],
            'multiple traits in one use statement' => [ $handler . ' trait EmptyHandler {} class Example { use EmptyHandler, Handler; ' . $run . ' }', 'resource' ],
            'separate trait uses' => [ $handler . ' trait EmptyHandler {} class Example { use EmptyHandler; use Handler; ' . $run . ' }', 'resource' ],
            'trait overrides parent' => [ $handler . $parent . ' class Example extends BaseHandler { use Handler; ' . $run . ' }', 'resource' ],
            'class overrides trait' => [ $handler . ' class Example { use Handler; public function handle(object $localResource, string $ability): void {} ' . $run . ' }', 'localResource' ],
            'parent uses trait' => [ $handler . ' class BaseHandler { use Handler; } class Example extends BaseHandler { ' . $run . ' }', 'resource' ],
            'unrelated trait allows parent lookup' => [ 'trait EmptyHandler {} ' . $parent . ' class Example extends BaseHandler { use EmptyHandler; ' . $run . ' }', 'parentResource' ],
            'unknown trait' => [ $parent . ' class Example extends BaseHandler { use UnknownHandler; ' . $run . ' }', null ],
            'conflicting traits' => [ $handler . ' trait OtherHandler { public function handle(object $otherResource, string $ability): void {} } ' . $parent . ' class Example extends BaseHandler { use Handler, OtherHandler; ' . $run . ' }', null ],
            'adapted trait' => [ $handler . $parent . ' class Example extends BaseHandler { use Handler { handle as private; } ' . $run . ' }', null ],
            'cyclic traits' => [ 'trait FirstHandler { use SecondHandler; } trait SecondHandler { use FirstHandler; } class Example { use FirstHandler; ' . $run . ' }', null ],
            'unknown method' => [ 'trait EmptyHandler {} class Example { use EmptyHandler; ' . $run . ' }', null ],
            'variadic trait method' => [ 'trait Handler { public function handle(object $resource, string ...$abilities): void {} } class Example { use Handler; ' . $run . ' }', null ],
        ];
    }

    public function test_nested_calls_still_expand_when_their_own_arguments_exceed_the_limit(): void
    {
        $input = "<?php\nouter(fn () => inner(\$firstArgumentWithALongName, \$secondArgumentWithALongName));";
        $expected = "<?php\nouter(fn () => inner(\n    \$firstArgumentWithALongName,\n    \$secondArgumentWithALongName\n));";

        $this->assertSame($expected, $this->fix($input, 60));
        $this->assertSame($expected, $this->fix($expected, 60));
    }

    public function test_nested_call_length_boundary_excludes_the_enclosing_callback(): void
    {
        $base = "inner('', 'second')";
        $atLimit = str_replace("''", "'" . str_repeat('x', 140 - strlen($base)) . "'", $base);
        $aboveLimit = str_replace("''", "'" . str_repeat('x', 141 - strlen($base)) . "'", $base);
        $input = "<?php\nouter(fn () => " . $atLimit . ');';
        $expected = "<?php\nouter(fn () => " . str_replace([ '(', ', ', ')' ], [ "(\n    ", ",\n    ", "\n)" ], $aboveLimit) . ');';

        $this->assertSame($input, $this->fix($input));
        $this->assertSame($expected, $this->fix("<?php\nouter(fn () => " . $aboveLimit . ');'));
        $this->assertSame($expected, $this->fix($expected));
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

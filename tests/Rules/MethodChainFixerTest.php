<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Rules;

use Milewski\ECS\Fixers\MethodChainFixer;
use Milewski\ECS\Tests\Support\FixerTestCase;
use PhpCsFixer\ConfigurationException\InvalidFixerConfigurationException;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use SplFileInfo;

final class MethodChainFixerTest extends FixerTestCase
{
    #[DataProvider('provideReceiverLayouts')]
    public function test_previously_wrapped_chains_join_the_first_call_to_the_receiver(string $receiver): void
    {
        $input = "<?php\n" . $receiver . "\n    ->first()\n    ->second();";
        $expected = "<?php\n" . $receiver . "->first()\n    ->second();";

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public static function provideReceiverLayouts(): array
    {
        return [
            'this' => [ '$this' ],
            'local variable' => [ '$repository' ],
            'array element' => [ '$repositories[\'primary\']' ],
            'array index containing a call' => [ '$repositories[key()]' ],
            'array index containing a property' => [ '$repositories[$profile->primaryKey]' ],
            'grouped variable' => [ '($repository)' ],
            'nested grouped variable' => [ '(($repository))' ],
        ];
    }

    #[DataProvider('providePropertyReceivers')]
    public function test_wrapped_property_chains_begin_after_the_receiver_expression(string $receiver): void
    {
        $input = "<?php\n" . $receiver . "->first()\n    ->second();";
        $expected = "<?php\n" . $receiver . "\n    ->first()\n    ->second();";

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public static function providePropertyReceivers(): array
    {
        return [
            'local variable property' => [ '$role->permissions' ],
            'this property' => [ '$this->repository' ],
            'nested property' => [ '$this->services->repository' ],
            'nullsafe property' => [ '$role?->permissions' ],
            'dynamic property' => [ '$role->$property' ],
            'static property' => [ 'Registry::$permissions' ],
            'property array element' => [ '$role->permissions[\'primary\']' ],
            'grouped property' => [ '($role->permissions)' ],
            'property after a factory' => [ 'getRole()->permissions' ],
        ];
    }

    #[DataProvider('provideCallableFactories')]
    public function test_grouped_callable_factories_start_the_wrapped_chain(string $receiver): void
    {
        $input = "<?php\n" . $receiver . "->first()\n    ->second();";
        $expected = "<?php\n" . $receiver . "\n    ->first()\n    ->second();";

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public static function provideCallableFactories(): array
    {
        return [
            'array callable' => [ '($callbacks[\'primary\']())' ],
            'grouped array callable' => [ '(($callbacks[\'primary\'])())' ],
        ];
    }

    #[DataProvider('provideExcludedContexts')]
    public function test_control_headers_and_sprintf_preserve_their_chains(string $input): void
    {
        $input = "<?php\n" . $input;

        $this->assertSame($input, $this->fix($input, 10));
    }

    public static function provideExcludedContexts(): array
    {
        return [
            'if header' => [ 'if ($role->permissions->first()->all()) {}' ],
            'foreach header' => [ 'foreach ($role->permissions->first()->all() as $permission) {}' ],
            'sprintf arguments' => [ 'sprintf(\'Permissions: %s\', $role->permissions->first()->all());' ],
        ];
    }

    public function test_a_wrapped_property_link_wraps_the_remaining_short_links(): void
    {
        $input = "<?php\n\$role->permissions\n    ->first()->second();";
        $expected = "<?php\n\$role->permissions\n    ->first()\n    ->second();";

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public function test_nullsafe_property_and_method_operators_are_preserved(): void
    {
        $input = "<?php\n\$role?->permissions?->first()\n    ?->second();";
        $expected = "<?php\n\$role?->permissions\n    ?->first()\n    ?->second();";

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    #[DataProvider('provideSingleCallReceivers')]
    public function test_a_single_method_call_joins_its_variable_receiver(string $receiver, string $operator): void
    {
        $input = "<?php\n" . $receiver . "\n    " . $operator . 'permissions();';
        $expected = "<?php\n" . $receiver . $operator . 'permissions();';

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public static function provideSingleCallReceivers(): array
    {
        return [
            'local variable' => [ '$role', '->' ],
            'this' => [ '$this', '->' ],
            'nullsafe variable' => [ '$role', '?->' ],
            'grouped variable' => [ '($role)', '->' ],
            'array element' => [ '$roles[\'primary\']', '->' ],
        ];
    }

    public function test_single_receiver_comments_prevent_joining(): void
    {
        $input = "<?php\n\$role\n    // Explain permissions.\n    ->permissions();";

        $this->assertSame($input, $this->fix($input));
    }

    public function test_ternary_property_chain_starts_after_the_property(): void
    {
        $input = <<<'PHP'
        <?php
        return $role === null ? $profile->permissions() : $role->permissions->whereIn('name', array_column(CrmPermission::cases(), 'value'))
            ->pluck('name')
            ->all();
        PHP;

        $expected = <<<'PHP'
        <?php
        return $role === null ? $profile->permissions() : $role->permissions
            ->whereIn('name', array_column(CrmPermission::cases(), 'value'))
            ->pluck('name')
            ->all();
        PHP;

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public function test_ternary_direct_methods_keep_the_first_call_with_the_variable(): void
    {
        $input = <<<'PHP'
        <?php
        return $role === null ? $profile->permissions() : $role
            ->whereIn('name', array_column(CrmPermission::cases(), 'value'))
            ->pluck('name')
            ->all();
        PHP;

        $expected = <<<'PHP'
        <?php
        return $role === null ? $profile->permissions() : $role->whereIn('name', array_column(CrmPermission::cases(), 'value'))
            ->pluck('name')
            ->all();
        PHP;

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public function test_an_existing_nullsafe_chain_keeps_its_first_call_inline(): void
    {
        $input = "<?php\n\$repository\n    ?->find(\$id)\n    ?->all();";
        $expected = "<?php\n\$repository?->find(\$id)\n    ?->all();";

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public function test_comments_between_the_receiver_and_first_method_are_preserved(): void
    {
        $input = <<<'PHP'
        <?php
        $repository
            // Explain the lookup.
            ->find($id)
            ->all();
        $repository /* lookup */
            ->find($id)
            ->all();
        PHP;

        $this->assertSame($input, $this->fix($input));
        $this->assertSame($input, $this->fix($input, 20));
    }

    public function test_first_calls_with_multiline_arguments_keep_the_receiver_inline(): void
    {
        $input = <<<'PHP'
        <?php
        $repository
            ->find(
                first: $first,
                second: $second,
            )
            ->all();
        PHP;

        $expected = <<<'PHP'
        <?php
        $repository->find(
                first: $first,
                second: $second,
            )
            ->all();
        PHP;

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public function test_existing_chains_are_repaired_with_tabs_and_crlf(): void
    {
        $input = "<?php\r\n\t\$repository\r\n\t\t->find(\$id)\r\n\t\t->all();\r\n";
        $expected = "<?php\r\n\t\$repository->find(\$id)\r\n\t\t->all();\r\n";
        $whitespaces = new WhitespacesFixerConfig("\t", "\r\n");

        $this->assertSame($expected, $this->fix($input, 120, $whitespaces));
        $this->assertSame($expected, $this->fix($expected, 120, $whitespaces));
    }

    #[DataProvider('provideChains')]
    public function test_long_method_chains(string $input, string $expected): void
    {
        $input = "<?php\n" . $input . ";\n";
        $expected = "<?php\n" . $expected . ";\n";

        $this->assertSame($expected, $this->fix($input, 40));
        $this->assertSame($expected, $this->fix($expected, 40));
    }

    public static function provideChains(): array
    {
        return [
            'object property receiver' => [
                '$this->repository->findMatchingRecords($criteria)->map($callback)->all()',
                '$this->repository' . "\n    " . '->findMatchingRecords($criteria)' . "\n    " . '->map($callback)' . "\n    " . '->all()',
            ],
            'nullsafe calls' => [
                '$repository?->findMatchingRecords($criteria)?->map($callback)?->all()',
                '$repository?->findMatchingRecords($criteria)' . "\n    " . '?->map($callback)' . "\n    " . '?->all()',
            ],
            'static factory receiver' => [
                'Repository::query()->findMatchingRecords($criteria)->all()',
                'Repository::query()' . "\n    " . '->findMatchingRecords($criteria)' . "\n    " . '->all()',
            ],
            'function factory receiver' => [
                'repository_factory()->findMatchingRecords($criteria)->all()',
                'repository_factory()' . "\n    " . '->findMatchingRecords($criteria)' . "\n    " . '->all()',
            ],
            'grouped receiver' => [
                '(Repository::query())->findMatchingRecords($criteria)->all()',
                '(Repository::query())' . "\n    " . '->findMatchingRecords($criteria)' . "\n    " . '->all()',
            ],
            'new object receiver' => [
                'new Repository()->findMatchingRecords($criteria)->all()',
                'new Repository()' . "\n    " . '->findMatchingRecords($criteria)' . "\n    " . '->all()',
            ],
            'array element receiver' => [
                '$repositories[\'primary\']->findMatchingRecords($criteria)->all()',
                '$repositories[\'primary\']->findMatchingRecords($criteria)' . "\n    " . '->all()',
            ],
            'dynamic method name' => [
                '$repository->$methodWithALongName($criteria)->all()',
                '$repository->$methodWithALongName($criteria)' . "\n    " . '->all()',
            ],
            'nested short chain in callback' => [
                '$repository->map(fn ($value) => $value->first()->second())->all()',
                '$repository->map(fn ($value) => $value->first()->second())' . "\n    " . '->all()',
            ],
            'comments between calls' => [
                '$repository->first($criteria) /* explain */ ->second()',
                '$repository->first($criteria) /* explain */' . "\n    " . '->second()',
            ],
            'property after final call' => [
                '$repository->findMatchingRecords($criteria)->first()->value',
                '$repository->findMatchingRecords($criteria)' . "\n    " . '->first()->value',
            ],
            'assignment prefix exceeds the limit' => [
                '$membershipWithALongName = $query->first()->last()',
                '$membershipWithALongName = $query->first()' . "\n    " . '->last()',
            ],
            'coalescing suffix exceeds the limit' => [
                '$query->first()->last() ?? new MembershipFallbackWithALongName()',
                '$query->first()' . "\n    " . '->last() ?? new MembershipFallbackWithALongName()',
            ],
            'prefix and suffix exceed the limit' => [
                '$membership = $query->first()->last() ?? new Membership()',
                '$membership = $query->first()' . "\n    " . '->last() ?? new Membership()',
            ],
        ];
    }

    public function test_short_chains_and_property_accesses_are_preserved(): void
    {
        $input = <<<'PHP'
        <?php
        $repository->first()->all();
        $this->repository->find($id);
        $this->ticket->customer->profile->email;
        $repository->singleMethod('a long string which cannot be shortened by breaking a method chain');
        PHP;

        $this->assertSame($input, $this->fix($input, 40));
    }

    public function test_default_chain_length_boundary(): void
    {
        $base = '$repository->first(\'\')->last();';
        $atLimit = '$repository->first(\'' . str_repeat('x', 120 - strlen($base)) . '\')->last();';
        $aboveLimit = '$repository->first(\'' . str_repeat('x', 121 - strlen($base)) . '\')->last();';
        $input = "<?php\n" . $atLimit . "\n";

        $this->assertSame($input, $this->fix($input));
        $this->assertSame(
            expected: "<?php\n" . str_replace('->last()', "\n    ->last()", $aboveLimit) . "\n",
            actual: $this->fix("<?php\n" . $aboveLimit . "\n"),
        );
    }

    public function test_chain_wrapping_respects_indentation_and_line_endings(): void
    {
        $input = "<?php\r\n\t\$repository->findMatchingRecords(\$criteria)->all();\r\n";
        $expected = "<?php\r\n\t\$repository->findMatchingRecords(\$criteria)\r\n\t\t->all();\r\n";

        $this->assertSame($expected, $this->fix($input, 40, new WhitespacesFixerConfig("\t", "\r\n")));
    }

    #[DataProvider('provideNestedChainContexts')]
    public function test_short_nested_chains_exclude_the_outer_call_and_indentation(string $context): void
    {
        $chain = '$notes->take(CrmPageData::SIZE)->map(fn (CustomerNote $note): CustomerNoteData => $this->presentNote($note))->all()';
        $input = "<?php\n" . str_replace('__CHAIN__', $chain, $context);

        $this->assertSame($input, $this->fix($input));
    }

    public static function provideNestedChainContexts(): array
    {
        return [
            'inline constructor' => [ '        return new CustomerNotePageData(__CHAIN__, $hasMore);' ],
            'expanded constructor' => [
                "        return new CustomerNotePageData(\n            notes: __CHAIN__,\n            hasMore: \$hasMore,\n        );",
            ],
            'deeply indented constructor' => [
                "                return new CustomerNotePageData(\n                    notes: __CHAIN__,\n                    hasMore: \$hasMore,\n                );",
            ],
            'function argument' => [ '        consume(__CHAIN__, $hasMore);' ],
            'named method argument' => [
                "        \$receiver->consume(\n            notes: __CHAIN__,\n            hasMore: \$hasMore,\n        );",
            ],
        ];
    }

    public function test_nested_chain_length_boundary(): void
    {
        $base = '$notes->take(\'\')->all()';
        $atLimit = str_replace("''", "'" . str_repeat('x', 120 - strlen($base)) . "'", $base);
        $aboveLimit = str_replace("''", "'" . str_repeat('x', 121 - strlen($base)) . "'", $base);
        $input = "<?php\n        consume(" . $atLimit . ', $other);';

        $this->assertSame($input, $this->fix($input));
        $this->assertSame(
            expected: "<?php\n        consume(" . str_replace('->all()', "\n            ->all()", $aboveLimit) . ', $other);',
            actual: $this->fix("<?php\n        consume(" . $aboveLimit . ', $other);'),
        );
    }

    #[DataProvider('provideInvalidLimits')]
    public function test_invalid_line_length_limits_are_rejected(mixed $limit): void
    {
        $fixer = new MethodChainFixer();

        $this->expectException(InvalidFixerConfigurationException::class);

        $fixer->configure([ 'max_line_length' => $limit ]);
    }

    public static function provideInvalidLimits(): array
    {
        return [ [ 0 ], [ -1 ], [ '120' ], [ 120.0 ] ];
    }

    private function fix(string $input, int $maxLineLength = 120, ?WhitespacesFixerConfig $whitespaces = null): string
    {
        $tokens = Tokens::fromCode($input);
        $fixer = new MethodChainFixer();
        $fixer->configure([ 'max_line_length' => $maxLineLength ]);

        if ($whitespaces !== null) {
            $fixer->setWhitespacesConfig($whitespaces);
        }

        $fixer->fix(new SplFileInfo('fixture.php'), $tokens);

        return $tokens->generateCode();
    }
}

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
                '$repository' . "\n    " . '?->findMatchingRecords($criteria)' . "\n    " . '?->map($callback)' . "\n    " . '?->all()',
            ],
            'static factory receiver' => [
                'Repository::query()->findMatchingRecords($criteria)->all()',
                'Repository::query()' . "\n    " . '->findMatchingRecords($criteria)' . "\n    " . '->all()',
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
                '$repositories[\'primary\']' . "\n    " . '->findMatchingRecords($criteria)' . "\n    " . '->all()',
            ],
            'dynamic method name' => [
                '$repository->$methodWithALongName($criteria)->all()',
                '$repository' . "\n    " . '->$methodWithALongName($criteria)' . "\n    " . '->all()',
            ],
            'nested short chain in callback' => [
                '$repository->map(fn ($value) => $value->first()->second())->all()',
                '$repository' . "\n    " . '->map(fn ($value) => $value->first()->second())' . "\n    " . '->all()',
            ],
            'comments between calls' => [
                '$repository->first($criteria) /* explain */ ->second()',
                '$repository' . "\n    " . '->first($criteria) /* explain */' . "\n    " . '->second()',
            ],
            'property after final call' => [
                '$repository->findMatchingRecords($criteria)->first()->value',
                '$repository' . "\n    " . '->findMatchingRecords($criteria)' . "\n    " . '->first()->value',
            ],
            'assignment prefix exceeds the limit' => [
                '$membershipWithALongName = $query->first()->last()',
                '$membershipWithALongName = $query' . "\n    " . '->first()' . "\n    " . '->last()',
            ],
            'coalescing suffix exceeds the limit' => [
                '$query->first()->last() ?? new MembershipFallbackWithALongName()',
                '$query' . "\n    " . '->first()' . "\n    " . '->last() ?? new MembershipFallbackWithALongName()',
            ],
            'prefix and suffix exceed the limit' => [
                '$membership = $query->first()->last() ?? new Membership()',
                '$membership = $query' . "\n    " . '->first()' . "\n    " . '->last() ?? new Membership()',
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

    #[DataProvider('providePartiallyMultilineChains')]
    public function test_multiline_chains_put_each_continuation_on_its_own_line(string $input, string $expected): void
    {
        $input = "<?php\n" . $input . ";\n";
        $expected = "<?php\n" . $expected . ";\n";

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public static function providePartiallyMultilineChains(): array
    {
        return [
            'static factory with inline first filter' => [
                "Customer::query()->where('affiliate_id', \$affiliateId)\n    ->get()",
                "Customer::query()\n    ->where('affiliate_id', \$affiliateId)\n    ->get()",
            ],
            'inline calls before and after a split' => [
                "\$this->velocityWindow(\$now)->where('affiliate_id', \$affiliateId)\n    ->selectRaw('count(*) as day_count')->toBase()->first()",
                "\$this->velocityWindow(\$now)\n    ->where('affiliate_id', \$affiliateId)\n    ->selectRaw('count(*) as day_count')\n    ->toBase()\n    ->first()",
            ],
            'short mixed chain' => [
                "\$query->first()\n    ->second()->all()",
                "\$query->first()\n    ->second()\n    ->all()",
            ],
            'receiver already on a separate line' => [
                "\$query\n    ->first()->all()",
                "\$query\n    ->first()\n    ->all()",
            ],
            'nullsafe continuations' => [
                "\$query?->first()\n    ?->second()?->all()",
                "\$query?->first()\n    ?->second()\n    ?->all()",
            ],
            'nested chains stay independent' => [
                "\$query->map(fn (\$item) => \$item->first()->last())\n    ->values()->all()",
                "\$query->map(fn (\$item) => \$item->first()->last())\n    ->values()\n    ->all()",
            ],
            'multiline arguments alone do not split the chain' => [
                "\$query->where(\n    'id',\n    \$id,\n)->get()",
                "\$query->where(\n    'id',\n    \$id,\n)->get()",
            ],
            'multiline chain inside a control header is preserved' => [
                "if (\$query->first()\n    ->second()->exists()) {}",
                "if (\$query->first()\n    ->second()->exists()) {}",
            ],
            'multiline boolean operand is preserved' => [
                "\$allowed = \$query->first()\n    ->second()->exists() === true",
                "\$allowed = \$query->first()\n    ->second()->exists() === true",
            ],
            'multiline chain inside sprintf is preserved' => [
                "sprintf('%s', \$query->first()\n    ->second()->all())",
                "sprintf('%s', \$query->first()\n    ->second()->all())",
            ],
        ];
    }

    public function test_partially_multiline_chain_wrapping_respects_tabs_and_crlf(): void
    {
        $input = "<?php\r\n\t\$this->query()->first()\r\n\t\t->last()->all();\r\n";
        $expected = "<?php\r\n\t\$this->query()\r\n\t\t->first()\r\n\t\t->last()\r\n\t\t->all();\r\n";
        $whitespaces = new WhitespacesFixerConfig("\t", "\r\n");

        $this->assertSame($expected, $this->fix($input, 120, $whitespaces));
        $this->assertSame($expected, $this->fix($expected, 120, $whitespaces));
    }

    public function test_the_initial_receiver_and_clean_payment_chain_are_preserved(): void
    {
        $input = <<<'PHP'
        <?php
        final class AffiliateQueries
        {
            public function mcpPayments(AffiliatePayoutListFilterData $filters): LengthAwarePaginator
            {
                return $this->withWeeklyPayout(AffiliatePayout::query())
                    ->when($filters->affiliateId !== null, static fn (Builder $sent): Builder => $sent->where('affiliate_id', $filters->affiliateId))
                    ->when($filters->from !== null, static fn (Builder $sent): Builder => $sent->where('paid_on', '>=', $filters->from))
                    ->when($filters->until !== null, static fn (Builder $sent): Builder => $sent->where('paid_on', '<=', $filters->until))
                    ->orderByDesc('paid_on')
                    ->orderByDesc('created_at')
                    ->orderBy('id')
                    ->paginate($filters->perPage, [ '*' ], 'page', $filters->page);
            }
        }
        PHP;

        $this->assertSame($input, $this->fix($input));
        $this->assertSame($input, $this->fix($this->fix($input)));
    }

    public function test_default_chain_length_boundary(): void
    {
        $base = '$repository->first(\'\')->last();';
        $atLimit = '$repository->first(\'' . str_repeat('x', 120 - strlen($base)) . '\')->last();';
        $aboveLimit = '$repository->first(\'' . str_repeat('x', 121 - strlen($base)) . '\')->last();';
        $input = "<?php\n" . $atLimit . "\n";

        $this->assertSame($input, $this->fix($input));
        $this->assertSame(
            expected: "<?php\n" . str_replace('->', "\n    ->", $aboveLimit) . "\n",
            actual: $this->fix("<?php\n" . $aboveLimit . "\n"),
        );
    }

    public function test_chain_wrapping_respects_indentation_and_line_endings(): void
    {
        $input = "<?php\r\n\t\$repository->findMatchingRecords(\$criteria)->all();\r\n";
        $expected = "<?php\r\n\t\$repository\r\n\t\t->findMatchingRecords(\$criteria)\r\n\t\t->all();\r\n";

        $this->assertSame($expected, $this->fix($input, 40, new WhitespacesFixerConfig("\t", "\r\n")));
    }

    #[DataProvider('provideChainSpacing')]
    public function test_chain_links_have_no_blank_lines(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public static function provideChainSpacing(): array
    {
        return [
            'short query chain' => [
                "<?php\n\$query\n    ->whereNull('sent_at')\n\n    ->get();\n",
                "<?php\n\$query\n    ->whereNull('sent_at')\n    ->get();\n",
            ],
            'single method after static factory' => [
                "<?php\nQuery::create()\n\n    ->get();\n",
                "<?php\nQuery::create()\n    ->get();\n",
            ],
            'nullsafe links and whitespace-only lines' => [
                "<?php\n\$query\n \t\n\n    ?->first()\n \n    ?->value;\n",
                "<?php\n\$query\n    ?->first()\n    ?->value;\n",
            ],
            'callback chain inside a condition' => [
                "<?php\nif (\$query->where(fn (\$nested) => \$nested\n\n    ->active())\n\n    ->exists()) {}\n",
                "<?php\nif (\$query->where(fn (\$nested) => \$nested\n    ->active())\n    ->exists()) {}\n",
            ],
            'inline links and separate statements' => [
                "<?php\n\$query->first()->value;\n\n\$other->get();\n",
                "<?php\n\$query->first()->value;\n\n\$other->get();\n",
            ],
            'comments and literal newlines' => [
                "<?php\n\$query->where('notes', \"first\n\nlast\")\n    // Keep this filter.\n    ->get();\n",
                "<?php\n\$query->where('notes', \"first\n\nlast\")\n    // Keep this filter.\n    ->get();\n",
            ],
        ];
    }

    public function test_chain_spacing_preserves_crlf_and_tab_indentation(): void
    {
        $input = "<?php\r\n\$query\r\n\r\n\t->first()\r\n\t\r\n\t->all();\r\n";
        $expected = "<?php\r\n\$query\r\n\t->first()\r\n\t->all();\r\n";

        $this->assertSame($expected, $this->fix($input, 120, new WhitespacesFixerConfig("\t", "\r\n")));
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
            'expanded constructor' => [ "        return new CustomerNotePageData(\n            notes: __CHAIN__,\n            hasMore: \$hasMore,\n        );" ],
            'deeply indented constructor' => [ "                return new CustomerNotePageData(\n                    notes: __CHAIN__,\n                    hasMore: \$hasMore,\n                );" ],
            'function argument' => [ '        consume(__CHAIN__, $hasMore);' ],
            'named method argument' => [ "        \$receiver->consume(\n            notes: __CHAIN__,\n            hasMore: \$hasMore,\n        );" ],
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
            expected: "<?php\n        consume(" . str_replace('->', "\n            ->", $aboveLimit) . ', $other);',
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

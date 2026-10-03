<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Rules;

use Milewski\ECS\Fixers\MethodChainFixer;
use Milewski\ECS\Fixers\MultilineNamedArgumentsFixer;
use Milewski\ECS\Tests\Support\FixerTestCase;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\Attributes\DataProvider;
use SplFileInfo;

final class ControlStructureWrappingTest extends FixerTestCase
{
    #[DataProvider('provideHeaders')]
    public function test_calls_and_chains_in_headers_are_preserved(string $input): void
    {
        $input = "<?php\n" . $input . "\n";

        $this->assertSame($input, $this->fix($input, 40));
        $this->assertSame($input, $this->fix($this->fix($input, 40), 40));
    }

    public static function provideHeaders(): array
    {
        $cases = [];
        $expressions = [
            'call' => 'matches($firstLongValue, $secondLongValue)',
            'chain' => 'Repository::query()->where($firstLongValue, $secondLongValue)->exists()',
        ];

        $headers = [
            'if' => 'if (%s) { keep(); }',
            'negated nested condition' => 'if (!$ready && (%s) === true) { keep(); }',
            'nested calls' => 'if (guard(%s, $thirdLongValue)) { keep(); }',
            'elseif' => 'if ($ready) { keep(); } elseif (%s) { keep(); }',
            'else if' => 'if ($ready) { keep(); } else if (%s) { keep(); }',
            'while' => 'while (%s) { keep(); }',
            'do while' => 'do { keep(); } while (%s);',
            'for condition' => 'for ($i = 0; %s; $i++) { keep(); }',
            'for initializer' => 'for ($value = %s; $value; $value = null) { keep(); }',
            'for increment' => 'for ($i = 0; $i < 10; $value = %s) { keep(); }',
            'foreach' => 'foreach (%s as $value) { keep(); }',
            'alternative syntax' => 'if (%s): keep(); endif;',
            'match subject' => '$result = match (%s) { true => 1, default => 0 };',
            'switch subject' => 'switch (%s) { default: break; }',
            'comment before condition' => 'if /* explain */ (%s) { keep(); }',
            'multiline header' => "if (\n    \$ready && %s\n) { keep(); }",
            'nested control structures' => 'if ($ready) { while (%s) { keep(); } }',
        ];

        foreach ($expressions as $expressionName => $expression) {

            foreach ($headers as $headerName => $header) {
                $cases[ $expressionName . ' in ' . $headerName ] = [ sprintf($header, $expression) ];
            }

        }

        $cases[ 'nullsafe named method call' ] = [
            'if ($service?->matches(first: $firstLongValue, second: $secondLongValue) === true) { keep(); }',
        ];

        $cases[ 'nullsafe chain' ] = [
            'if ($service?->find($firstLongValue, $secondLongValue)?->exists() === true) { keep(); }',
        ];

        $cases[ 'constructor call' ] = [
            'if (new Specification($firstLongValue, $secondLongValue) instanceof Specification) { keep(); }',
        ];

        $cases[ 'dynamic callable' ] = [
            'if ($callbacks[\'matches\']($firstLongValue, $secondLongValue)) { keep(); }',
        ];

        return $cases;
    }

    public function test_the_reported_path_condition_stays_inline_at_the_default_limit(): void
    {
        $input = <<<'PHP'
        <?php
        final class Collector
        {
            public function collect(): void
            {
                if ($ready) {
                    if ($normalizedConfiguredPath !== null && $this->pathIsWithin($normalizedPath, $normalizedConfiguredPath)) {
                        $pathOwners[] = $connection;
                    }
                }
            }

            private function pathIsWithin(string $path, string $directory): bool
            {
                return true;
            }
        }
        PHP;

        $this->assertSame($input, $this->fix($input));
    }

    public function test_calls_inside_if_and_else_bodies_still_wrap(): void
    {
        $input = <<<'PHP'
        <?php
        if (matches($firstLongValue, $secondLongValue)) {
            unknown_call($firstLongValue, $secondLongValue);
        } else {
            unknown_call($firstLongValue, $secondLongValue);
        }
        PHP;

        $expected = <<<'PHP'
        <?php
        if (matches($firstLongValue, $secondLongValue)) {
            unknown_call(
                $firstLongValue,
                $secondLongValue
            );
        } else {
            unknown_call(
                $firstLongValue,
                $secondLongValue
            );
        }
        PHP;

        $this->assertSame($expected, $this->fix($input, 40));
        $this->assertSame($expected, $this->fix($expected, 40));
    }

    public function test_assignment_and_coalescing_wrap_the_chain_before_short_arguments(): void
    {
        $input = <<<'PHP'
        <?php
                    $membership = CrmMembership::query()->where('user_id', $userId)->lockForUpdate()->first() ?? new CrmMembership();
        PHP;

        $expected = <<<'PHP'
        <?php
                    $membership = CrmMembership::query()
                        ->where('user_id', $userId)
                        ->lockForUpdate()
                        ->first() ?? new CrmMembership();
        PHP;

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public function test_a_when_callback_between_120_and_140_characters_stays_inline(): void
    {
        $input = <<<'PHP'
        <?php
        final class Tickets
        {
            public function findOrFail(int $id, bool $lock = false, ?array $knownLeadIds = null): Ticket
            {
                return $this->query()
                    ->when($knownLeadIds !== null, static fn (Builder $query): Builder => $query->whereIn('lead_id', $knownLeadIds))
                    ->when($lock, static fn (Builder $query): Builder => $query->lockForUpdate())
                    ->findOrFail($id);
            }
        }
        PHP;

        $this->assertSame($input, $this->fix($input));
        $this->assertSame($input, $this->fix($this->fix($input)));
    }

    private function fix(string $input, ?int $maxLineLength = null): string
    {
        $tokens = Tokens::fromCode($input);

        foreach ([ new MethodChainFixer(), new MultilineNamedArgumentsFixer() ] as $fixer) {

            $fixer->configure([
                'max_line_length' => $maxLineLength ?? ($fixer instanceof MethodChainFixer ? 120 : 140),
            ]);

            $fixer->fix(new SplFileInfo('fixture.php'), $tokens);

        }

        return $tokens->generateCode();
    }
}

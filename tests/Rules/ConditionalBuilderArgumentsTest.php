<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Rules;

use Milewski\ECS\Fixers\MultilineNamedArgumentsFixer;
use Milewski\ECS\Tests\Support\FixerTestCase;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\Attributes\DataProvider;
use SplFileInfo;

final class ConditionalBuilderArgumentsTest extends FixerTestCase
{
    #[DataProvider('provideBuilders')]
    public function test_outer_builder_assignments_resolve_inside_nested_branches(string $query): void
    {
        $input = $this->source($query);
        $expected = str_replace(
            search: [ "        'select ?'", '        [ $identity ]' ],
            replace: [ "        sql: 'select ?'", '        bindings: [ $identity ]' ],
            subject: $input,
        );

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public static function provideBuilders(): array
    {
        return [
            'plain builder' => [ 'Builder::query()' ],
            'when with typed arrow' => [ 'Builder::query()->when($ids !== null, static fn (Builder $query): Builder => $query->whereKey($ids))' ],
            'unless with typed arrow' => [ 'Builder::query()->unless($ids === null, static fn (Builder $query): Builder => $query->whereKey($ids))' ],
            'when with captured closure' => [ 'Builder::query()->when($ids !== null, function (Builder $query) use ($ids): Builder { return $query->whereKey($ids); })' ],
            'typed default' => [ 'Builder::query()->when($ready, static fn (Builder $query): Builder => $query, static fn (Builder $query): Builder => $query)' ],
            'named callbacks' => [ 'Builder::query()->when(value: $ready, default: static fn (Builder $query): Builder => $query, callback: static fn (Builder $query): Builder => $query)' ],
            'explicit null default' => [ 'Builder::query()->when($ready, static fn (Builder $query): Builder => $query, null)' ],
            'nullable callback return' => [ 'Builder::query()->when($ready, static fn (Builder $query): ?Builder => $query)' ],
        ];
    }

    #[DataProvider('provideUncertainBuilders')]
    public function test_uncertain_callback_return_types_do_not_guess_names(string $query): void
    {
        $input = $this->source($query);

        $this->assertSame($input, $this->fix($input));
    }

    public static function provideUncertainBuilders(): array
    {
        return [
            'untyped callback' => [ 'Builder::query()->when($ready, static fn (Builder $query) => $query)' ],
            'scalar return' => [ 'Builder::query()->when($ready, static fn (Builder $query): string => \'result\')' ],
            'different default return' => [ 'Builder::query()->when($ready, static fn (Builder $query): Builder => $query, static fn (): string => \'result\')' ],
            'higher order proxy' => [ 'Builder::query()->when($ready)' ],
            'dynamic callback' => [ 'Builder::query()->when($ready, $callback)' ],
            'union callback return' => [ 'Builder::query()->when($ready, static fn (Builder $query): Builder|string => $query)' ],
            'default expression beginning with null' => [ 'Builder::query()->when($ready, static fn (Builder $query): Builder => $query, null ?? static fn (): string => \'result\')' ],
            'unpacked default' => [ 'Builder::query()->when($ready, static fn (Builder $query): Builder => $query, ...$defaults)' ],
        ];
    }

    private function source(string $query): string
    {
        $source = <<<'PHP'
        <?php
        use Milewski\ECS\Tests\Support\ReflectionConditionalQuery as Builder;

        function search(): void
        {
            $query = __QUERY__;

            if ($email) {
                $query->whereRaw(
                    'select ?',
                    [ $identity ],
                );
            } else {
                if ($phone) {
                    $query->whereRaw(
                        'select ?',
                        [ $identity ],
                    );
                }
            }
        }
        PHP;

        return str_replace('__QUERY__', $query, $source);
    }

    private function fix(string $input): string
    {
        $tokens = Tokens::fromCode($input);
        $fixer = new MultilineNamedArgumentsFixer();
        $fixer->configure([ 'max_line_length' => 1000 ]);
        $fixer->fix(new SplFileInfo('fixture.php'), $tokens);

        return $tokens->generateCode();
    }
}

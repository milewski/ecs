<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Rules;

use Milewski\ECS\Fixers\PaddedArrayFixer;
use Milewski\ECS\Tests\Support\FixerTestCase;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use SplFileInfo;

final class ExpressionArrayLayoutTest extends FixerTestCase
{
    #[DataProvider('provideExpressionArrays')]
    public function test_expression_arrays_stay_inline_even_above_the_limit(string $code): void
    {
        $code = "<?php\n" . $code;

        $this->assertSame($code, $this->fix($code));
        $this->assertSame($code, $this->fix($this->fix($code)));
    }

    public static function provideExpressionArrays(): array
    {
        $array = "[ 'first permission', 'second permission' ]";

        return [
            'named comparison argument' => [ "abort_unless(boolean: array_diff($array, \$permissions) === [], code: 403);" ],
            'return comparison' => [ "return array_diff($array, \$permissions) !== [];" ],
            'logical expression' => [ "\$allowed = \$ready && includes($array);" ],
            'unary boolean expression' => [ "\$denied = !includes($array);" ],
            'ternary comparison condition' => [ "\$label = includes($array) === true ? 'yes' : 'no';" ],
            'if header' => [ "if (includes($array)) {}" ],
            'elseif header' => [ "if (\$ready) {} elseif (includes($array)) {}" ],
            'while header' => [ "while (includes($array)) {}" ],
            'do while header' => [ "do {} while (includes($array));" ],
            'for header' => [ "for (\$items = $array; \$ready; \$index++) {}" ],
            'foreach header' => [ "foreach ($array as \$permission) {}" ],
            'switch header' => [ "switch (select($array)) {}" ],
            'match subject' => [ "\$value = match (select($array)) { default => null };" ],
            'nested array operands' => [ "return includes([ $array, [] ]) === true;" ],
        ];
    }

    public function test_previously_expanded_expression_arrays_are_compacted(): void
    {
        $input = <<<'PHP'
        <?php
        abort_unless(
            boolean: array_diff([
                CrmPermissionRepository::ACCESS,
                'crm.settings.manage',
            ], $this->crmPermissionRepository->forUser($actorUserId)) === [],
            code: 403,
        );
        if (includes([
            [
                'first permission',
                'second permission',
            ],
        ])) {}
        $value = match ($key) {
            default => [
                'first permission',
                'second permission',
            ],
        };
        PHP;

        $expected = <<<'PHP'
        <?php
        abort_unless(
            boolean: array_diff([ CrmPermissionRepository::ACCESS, 'crm.settings.manage' ], $this->crmPermissionRepository->forUser($actorUserId)) === [],
            code: 403,
        );
        if (includes([ [ 'first permission', 'second permission' ] ])) {}
        $value = match ($key) {
            default => [
                'first permission',
                'second permission',
            ],
        };
        PHP;

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public function test_nested_and_adjacent_match_arms_preserve_multiline_arrays_without_affecting_subjects_or_following_conditions(): void
    {
        $input = <<<'PHP'
        <?php
        $first = match (select([
            'first',
            'second',
        ])) {
            default => match ($key) {
                default => [
                    [ 'name' => 'valid', 'value' => 'California' ],
                    [ 'name' => 'invalid', 'value' => 'Atlantis' ],
                ],
            },
        };
        $second = match ($key) {
            default => [
                'first',
                'second',
            ],
        };
        if (includes([
            'first',
            'second',
        ])) {}
        PHP;

        $expected = str_replace(
            [ "select([\n    'first',\n    'second',\n])", "includes([\n    'first',\n    'second',\n])" ],
            [ "select([ 'first', 'second' ])", "includes([ 'first', 'second' ])" ],
            $input,
        );

        $this->assertSame($expected, $this->fix($input, 140));
        $this->assertSame($expected, $this->fix($expected, 140));
    }

    public function test_match_array_preservation_respects_tabs_and_crlf_and_pads_inline_rows(): void
    {
        $input = "<?php\r\n\t\$value = match (\$key) {\r\n\t\tdefault => [\r\n\t\t\t['first'],\r\n\t\t\t[],\r\n\t\t],\r\n\t};\r\n";
        $expected = str_replace("['first']", "[ 'first' ]", $input);

        $whitespaces = new WhitespacesFixerConfig("\t", "\r\n");

        $this->assertSame($expected, $this->fix($input, 140, $whitespaces));
        $this->assertSame($expected, $this->fix($expected, 140, $whitespaces));
    }

    #[DataProvider('provideUnsafeCompactions')]
    public function test_comments_and_multiline_values_are_preserved(string $code): void
    {
        $code = "<?php\n" . $code;

        $this->assertSame($code, $this->fix($code));
    }

    public static function provideUnsafeCompactions(): array
    {
        return [
            'line comment' => [ "return includes([\n    'first', // Explain the value.\n    'second',\n]) === true;" ],
            'block comment' => [ "if (includes([ /* Keep this comment. */ 'first', 'second' ])) {}" ],
            'multiline string' => [ "return includes([ \"first line\nsecond line\" ]) === true;" ],
            'closure body' => [ "if (includes([ static function () {\n    return 'first';\n} ])) {}" ],
            'heredoc' => [ "if (includes([\n    <<<TEXT\n    first line\n    second line\n    TEXT,\n])) {}" ],
        ];
    }

    public function test_exclusions_do_not_escape_into_bodies_or_enclosing_arrays(): void
    {
        $input = <<<'PHP'
        <?php
        if (includes([ 'first', 'second' ])) {
            $items = [ 'first long item', 'second long item' ];
        }
        $data = [ 'allowed' => includes([ 'first', 'second' ]) === true, 'other' => 1 ];
        $items = [ match ($key) { default => [ 'first', 'second' ] }, 'other' ];
        PHP;

        $expected = <<<'PHP'
        <?php
        if (includes([ 'first', 'second' ])) {
            $items = [
                'first long item',
                'second long item'
            ];
        }
        $data = [
            'allowed' => includes([ 'first', 'second' ]) === true,
            'other' => 1
        ];
        $items = [
            match ($key) { default => [
                'first',
                'second'
            ] },
            'other'
        ];
        PHP;

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public function test_expression_array_compaction_respects_tabs_and_crlf(): void
    {
        $input = "<?php\r\n\tif (includes([\r\n\t\t'first',\r\n\t\t'second',\r\n\t])) {}\r\n";
        $expected = "<?php\r\n\tif (includes([ 'first', 'second' ])) {}\r\n";

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    private function fix(string $input, int $limit = 30, ?WhitespacesFixerConfig $whitespaces = null): string
    {
        $tokens = Tokens::fromCode($input);
        $fixer = new PaddedArrayFixer();
        $fixer->configure([ 'max_line_length' => $limit ]);
        $fixer->setWhitespacesConfig($whitespaces ?? new WhitespacesFixerConfig());
        $fixer->fix(new SplFileInfo('fixture.php'), $tokens);

        return $tokens->generateCode();
    }
}

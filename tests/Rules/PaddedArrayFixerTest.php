<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Rules;

use Milewski\ECS\Fixers\PaddedArrayFixer;
use Milewski\ECS\Tests\Support\FixerTestCase;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use SplFileInfo;

final class PaddedArrayFixerTest extends FixerTestCase
{
    #[DataProvider('provideMultilineArrays')]
    public function test_multiline_arrays_put_elements_and_brackets_on_separate_lines(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public static function provideMultilineArrays(): array
    {
        return [
            'single multiline constructor without padding' => [
                "<?php\n\$plans = [new BillingPlanData(\n    priceId: 'local-monthly',\n    amountCents: 29900,\n)];",
                "<?php\n\$plans = [\n    new BillingPlanData(\n    priceId: 'local-monthly',\n    amountCents: 29900,\n)\n];",
            ],
            'multiple multiline constructors with padding' => [
                "<?php\n\$plans = [ new BillingPlanData(\n    priceId: 'local-monthly',\n), new BillingPlanData(\n    priceId: 'local-quarterly',\n) ];",
                "<?php\n\$plans = [\n    new BillingPlanData(\n    priceId: 'local-monthly',\n),\n    new BillingPlanData(\n    priceId: 'local-quarterly',\n)\n];",
            ],
            'only the closing bracket needs a break' => [
                "<?php\n\$plans = [\n    new BillingPlanData(\n        amountCents: 29900,\n    )];",
                "<?php\n\$plans = [\n    new BillingPlanData(\n        amountCents: 29900,\n    )\n];",
            ],
            'only the opening bracket needs a break' => [
                "<?php\n\$plans = [new BillingPlanData(\n    amountCents: 29900,\n)\n];",
                "<?php\n\$plans = [\n    new BillingPlanData(\n    amountCents: 29900,\n)\n];",
            ],
            'mixed inline and multiline values' => [
                "<?php\n\$plans = [ 'default', new BillingPlanData(\n    amountCents: 29900,\n), 'fallback', ];",
                "<?php\n\$plans = [\n    'default',\n    new BillingPlanData(\n    amountCents: 29900,\n),\n    'fallback',\n];",
            ],
            'nested multiline arrays' => [
                "<?php\n\$plans = [[\n    'monthly', 'quarterly',\n]];",
                "<?php\n\$plans = [\n    [\n        'monthly',\n        'quarterly',\n    ]\n];",
            ],
            'nested call and closure commas stay inside their expressions' => [
                "<?php\n\$plans = [ build('monthly', 'quarterly'), function (\$first, \$second) {\n    return [\$first, \$second];\n}, 'fallback' ];",
                "<?php\n\$plans = [\n    build('monthly', 'quarterly'),\n    function (\$first, \$second) {\n    return [ \$first, \$second ];\n},\n    'fallback'\n];",
            ],
            'comments are retained' => [
                "<?php\n\$plans = [ /* first */ 'monthly', // fallback\n    'quarterly' /* last */ ];",
                "<?php\n\$plans = [\n    /* first */ 'monthly', // fallback\n    'quarterly' /* last */\n];",
            ],
            'hash comments stay attached to their element' => [
                "<?php\n\$plans = [ 'monthly', # plans\n    'quarterly' ];",
                "<?php\n\$plans = [\n    'monthly', # plans\n    'quarterly'\n];",
            ],
            'block comments move with the following element' => [
                "<?php\n\$plans = [ 'monthly', /* fallback */ 'quarterly'\n];",
                "<?php\n\$plans = [\n    'monthly',\n    /* fallback */ 'quarterly'\n];",
            ],
            'multiline comment triggers expansion' => [
                "<?php\n\$plans = [ /* first\nlast */ 'monthly' ];",
                "<?php\n\$plans = [\n    /* first\nlast */ 'monthly'\n];",
            ],
            'comment-only array is not empty' => [
                "<?php\n\$plans = [\n    // plans\n];",
                "<?php\n\$plans = [\n    // plans\n];",
            ],
            'multiple arrays are all visited' => [
                "<?php\n\$first = [build(\n    1,\n)];\n\$second = [build(\n    2,\n)];",
                "<?php\n\$first = [\n    build(\n    1,\n)\n];\n\$second = [\n    build(\n    2,\n)\n];",
            ],
        ];
    }

    #[DataProvider('provideCompactArraysAndOffsets')]
    public function test_compact_arrays_and_offsets_keep_their_padding(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public static function provideCompactArraysAndOffsets(): array
    {
        return [
            'no arrays' => [ '<?php $value = 1;', '<?php $value = 1;' ],
            'empty array' => [ '<?php $values = [  ];', '<?php $values = [];' ],
            'empty array without whitespace' => [ '<?php $values = [];', '<?php $values = [];' ],
            'empty multiline array' => [ "<?php \$values = [\n];", '<?php $values = [];' ],
            'compact values' => [ '<?php $values = [1, 2];', '<?php $values = [ 1, 2 ];' ],
            'existing compact padding' => [ '<?php $values = [  1, 2  ];', '<?php $values = [  1, 2  ];' ],
            'compact constructors' => [ '<?php $plans = [new Plan(), new Plan()];', '<?php $plans = [ new Plan(), new Plan() ];' ],
            'nested compact arrays' => [ '<?php $values = [[1], [2]];', '<?php $values = [ [ 1 ], [ 2 ] ];' ],
            'literal newline' => [ "<?php \$values = [\"first\nsecond\"];", "<?php \$values = [ \"first\nsecond\" ];" ],
            'array offset' => [ '<?php $value = $values[0];', '<?php $value = $values[ 0 ];' ],
            'array append' => [ '<?php $values[] = 1;', '<?php $values[] = 1;' ],
            'inline comment' => [ '<?php $values = [/* plans */];', '<?php $values = [ /* plans */ ];' ],
        ];
    }

    public function test_multiline_array_breaks_respect_configured_indentation_and_line_endings(): void
    {
        $input = "<?php\r\n\t\$plans = [new BillingPlanData(\r\n\t\tamountCents: 29900,\r\n\t)];";
        $expected = "<?php\r\n\t\$plans = [\r\n\t\tnew BillingPlanData(\r\n\t\tamountCents: 29900,\r\n\t)\r\n\t];";

        $this->assertSame($expected, $this->fix($input, new WhitespacesFixerConfig("\t", "\r\n")));
    }

    private function fix(string $input, ?WhitespacesFixerConfig $whitespaces = null): string
    {
        $tokens = Tokens::fromCode($input);
        $fixer = new PaddedArrayFixer();

        if ($whitespaces !== null) {
            $fixer->setWhitespacesConfig($whitespaces);
        }

        $fixer->fix(new SplFileInfo('fixture.php'), $tokens);

        return $tokens->generateCode();
    }
}

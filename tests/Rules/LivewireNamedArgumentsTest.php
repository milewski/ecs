<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Rules;

use Milewski\ECS\Fixers\MultilineNamedArgumentsFixer;
use Milewski\ECS\Tests\Support\FixerTestCase;
use Milewski\ECS\Tests\Support\ReflectionFilamentForms;
use Milewski\ECS\Tests\Support\ReflectionLivewireTestable;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\Attributes\DataProvider;
use SplFileInfo;
use stdClass;

final class LivewireNamedArgumentsTest extends FixerTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (class_exists('Livewire\\Features\\SupportTesting\\Testable', false) === false) {
            class_alias(ReflectionLivewireTestable::class, 'Livewire\\Features\\SupportTesting\\Testable');
        }

        if (class_exists('Filament\\Forms\\Testing\\TestsForms', false) === false) {
            class_alias(ReflectionFilamentForms::class, 'Filament\\Forms\\Testing\\TestsForms');
        }
    }

    protected function tearDown(): void
    {
        ReflectionLivewireTestable::flushMacros();

        parent::tearDown();
    }

    #[DataProvider('provideFormAssertions')]
    public function test_filament_macros_use_the_returned_closure_parameters(string $arguments, string $expectedArguments): void
    {
        $input = "<?php\nuse Milewski\\ECS\\Tests\\Support\\ReflectionLivewireFacade as Livewire;\n";
        $input .= "Livewire::test(CreateFaqEntry::class)\n    ->assertFormFieldExists(\n" . $arguments . "\n    );";
        $expected = str_replace($arguments, $expectedArguments, $input);

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public static function provideFormAssertions(): array
    {
        return [
            'callback passed in the form position' => [
                "        'category_id',\n        static fn (Select \$field): bool => !array_key_exists((string) \$faqCategory->getKey(), \$field->getOptions()),",
                "        key: 'category_id',\n        form: static fn (Select \$field): bool => !array_key_exists((string) \$faqCategory->getKey(), \$field->getOptions()),",
            ],
            'explicit form and callback' => [
                "        'category_id',\n        'faqForm',\n        static fn (Select \$field): bool => true,",
                "        key: 'category_id',\n        form: 'faqForm',\n        checkFieldUsing: static fn (Select \$field): bool => true,",
            ],
            'existing named callback' => [
                "        'category_id',\n        checkFieldUsing: static fn (Select \$field): bool => true,",
                "        key: 'category_id',\n        checkFieldUsing: static fn (Select \$field): bool => true,",
            ],
        ];
    }

    public function test_macros_preserve_the_receiver_type_in_fluent_chains(): void
    {
        $input = <<<'PHP'
        <?php
        use Milewski\ECS\Tests\Support\ReflectionLivewireFacade as Livewire;
        Livewire::test(CreateFaqEntry::class)
            ->fillForm(
                [],
                'faqForm',
            )
            ->assertFormFieldExists(
                'category_id',
                'faqForm',
            );
        PHP;

        $expected = str_replace(
            search: [ '        [],', "        'faqForm',", "        'category_id'," ],
            replace: [ '        state: [],', "        form: 'faqForm',", "        key: 'category_id'," ],
            subject: $input,
        );

        $this->assertSame($expected, $this->fix($input));
        $this->assertSame($expected, $this->fix($expected));
    }

    public function test_registered_macros_take_precedence_over_filament_defaults(): void
    {
        ReflectionLivewireTestable::macro('assertFormFieldExists', static fn (string $field, mixed $callback): bool => true);

        $input = "<?php\nuse Milewski\\ECS\\Tests\\Support\\ReflectionLivewireFacade as Livewire;\n";
        $input .= "Livewire::test(CreateFaqEntry::class)->assertFormFieldExists(\n    'category_id',\n    null,\n);";
        $expected = str_replace([ "    'category_id',", '    null,' ], [ "    field: 'category_id',", '    callback: null,' ], $input);

        $this->assertSame($expected, $this->fix($input));
    }

    public function test_unknown_and_ambiguous_macros_are_left_positional(): void
    {
        $input = <<<'PHP'
        <?php
        use Milewski\ECS\Tests\Support\ReflectionLivewireFacade as Livewire;
        Livewire::test(CreateFaqEntry::class)->unknownMacro(
            'category_id',
            'faqForm',
        );
        Livewire::test(CreateFaqEntry::class)->conditionalMacro(
            'category_id',
            'faqForm',
        );
        PHP;

        $this->assertSame($input, $this->fix($input));
    }

    public function test_variadic_registered_macros_are_left_positional(): void
    {
        ReflectionLivewireTestable::macro('customAssertion', static fn (string $field, mixed ...$checks): bool => true);

        $input = "<?php\nuse Milewski\\ECS\\Tests\\Support\\ReflectionLivewireFacade as Livewire;\n";
        $input .= "Livewire::test(CreateFaqEntry::class)->customAssertion(\n    'category_id',\n    'faqForm',\n);";

        $this->assertSame($input, $this->fix($input));
    }

    public function test_unsupported_registered_macros_do_not_use_filament_defaults(): void
    {
        ReflectionLivewireTestable::macro('assertFormFieldExists', new stdClass());

        $input = "<?php\nuse Milewski\\ECS\\Tests\\Support\\ReflectionLivewireFacade as Livewire;\n";
        $input .= "Livewire::test(CreateFaqEntry::class)->assertFormFieldExists(\n    'category_id',\n    'faqForm',\n);";

        $this->assertSame($input, $this->fix($input));
    }

    public function test_macro_names_are_case_sensitive(): void
    {
        $input = "<?php\nuse Milewski\\ECS\\Tests\\Support\\ReflectionLivewireFacade as Livewire;\n";
        $input .= "Livewire::test(CreateFaqEntry::class)->assertformfieldexists(\n    'category_id',\n    'faqForm',\n);";

        $this->assertSame($input, $this->fix($input));
    }

    public function test_private_mixin_helpers_are_not_treated_as_macros(): void
    {
        $input = "<?php\nuse Milewski\\ECS\\Tests\\Support\\ReflectionLivewireFacade as Livewire;\n";
        $input .= "Livewire::test(CreateFaqEntry::class)->privateMacro(\n    'category_id',\n    'faqForm',\n);";

        $this->assertSame($input, $this->fix($input));
    }

    private function fix(string $input): string
    {
        $tokens = Tokens::fromCode($input);
        $fixer = new MultilineNamedArgumentsFixer();
        $fixer->fix(new SplFileInfo('fixture.php'), $tokens);

        return $tokens->generateCode();
    }
}

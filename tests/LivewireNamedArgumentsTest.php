<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests;

use Milewski\ECS\Tests\Support\EcsTestCase;
use Milewski\ECS\Tests\Support\ReflectionFilamentForms;
use Milewski\ECS\Tests\Support\ReflectionLivewireTestable;

final class LivewireNamedArgumentsTest extends EcsTestCase
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

    public function test_livewire_form_assertion_receives_parameter_names_with_the_full_preset(): void
    {
        $input = <<<'PHP'
        <?php

        declare(strict_types = 1);

        use Milewski\ECS\Tests\Support\ReflectionLivewireFacade as Livewire;

        Livewire::test(CreateFaqEntry::class)
            ->assertFormFieldExists(
                'category_id',
                static fn (Select $field): bool => !array_key_exists((string) $faqCategory->getKey(), $field->getOptions()),
            );

        PHP;

        $expected = <<<'PHP'
        <?php

        declare(strict_types = 1);

        use Milewski\ECS\Tests\Support\ReflectionLivewireFacade as Livewire;

        Livewire::test(CreateFaqEntry::class)
            ->assertFormFieldExists(
                key: 'category_id',
                form: static fn (Select $field): bool => !array_key_exists((string) $faqCategory->getKey(), $field->getOptions()),
            );

        PHP;

        $this->assertCodeIsFixedTo($input, $expected);
        $this->assertCodeIsFixedTo($expected, $expected);
    }
}

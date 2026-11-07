<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Support;

use Closure;
use LogicException;

final class ReflectionFilamentForms
{
    public function assertFormFieldExists(): Closure
    {
        return function (string $key, string|Closure|null $form = null, ?Closure $checkFieldUsing = null): static {
            throw new LogicException('The returned macro must never be executed during formatting.');
        };
    }

    public function fillForm(): Closure
    {
        return function (array|Closure $state = [], ?string $form = null): static {
            throw new LogicException('The returned macro must never be executed during formatting.');
        };
    }

    public function conditionalMacro(): Closure
    {
        if (random_int(0, 1)) {
            return fn (string $first, string $second): string => $first;
        }

        return fn (string $different, string $parameters): string => $different;
    }

    private function privateMacro(): Closure
    {
        return fn (string $key, string $form): string => $key;
    }
}

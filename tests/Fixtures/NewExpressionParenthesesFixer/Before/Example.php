<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\NewExpressionParenthesesFixer;

final class Example
{
    public function build(): object
    {
        return (new MyClass())->abc();
    }
}

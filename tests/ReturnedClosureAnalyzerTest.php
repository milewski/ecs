<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests;

use ArrayObject;
use Milewski\ECS\Tests\Support\ReflectionClosureFactories;
use Milewski\ECS\Tests\Support\ReflectionFilamentForms;
use Milewski\ECS\TokenAnalyzer\ReturnedClosureAnalyzer;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Param;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class ReturnedClosureAnalyzerTest extends TestCase
{
    public function test_returned_closure_signature_includes_callback_parameters(): void
    {
        $analyzer = new ReturnedClosureAnalyzer();
        $method = new ReflectionMethod(ReflectionFilamentForms::class, 'assertFormFieldExists');
        $closure = $analyzer->find($method);

        $this->assertInstanceOf(Closure::class, $closure);
        $this->assertSame(
            expected: [ 'key', 'form', 'checkFieldUsing' ],
            actual: array_map(static fn (Param $parameter): string => $parameter->var->name, $closure->params),
        );

        $this->assertSame('static', $closure->returnType->toString());
    }

    public function test_returned_arrow_function_retains_variadic_parameters(): void
    {
        $closure = new ReturnedClosureAnalyzer()->find(new ReflectionMethod(ReflectionClosureFactories::class, 'arrowFactory'));

        $this->assertInstanceOf(ArrowFunction::class, $closure);
        $this->assertSame([ 'key', 'forms' ], array_map(static fn (Param $parameter): string => $parameter->var->name, $closure->params));
        $this->assertTrue($closure->params[ 1 ]->variadic);
    }

    #[DataProvider('provideUnsupportedFactories')]
    public function test_unsupported_factories_are_not_inferred(string $class, string $method): void
    {
        $analyzer = new ReturnedClosureAnalyzer();
        $factory = new ReflectionMethod($class, $method);

        $this->assertNull($analyzer->find($factory));
    }

    public static function provideUnsupportedFactories(): array
    {
        return [
            'conditional closure returns' => [ ReflectionFilamentForms::class, 'conditionalMacro' ],
            'factory with additional statements' => [ ReflectionClosureFactories::class, 'dynamicFactory' ],
            'returning a value instead of a closure' => [ ReflectionClosureFactories::class, 'nonClosureFactory' ],
            'factory with no body' => [ ReflectionClosureFactories::class, 'abstractFactory' ],
            'factory that throws' => [ ReflectionClosureFactories::class, 'throwingFactory' ],
            'internal method without source' => [ ArrayObject::class, 'getArrayCopy' ],
        ];
    }
}

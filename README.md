<p align="center">
    <img src="assets/icon.png" alt="ECS code-formatting icon" width="160" height="160">
</p>

# Milewski ECS

PHP coding-style configuration and custom fixers for Easy Coding Standard.

## Installation


- Add this repository to composer.json

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/milewski/ecs.git"
    }
]
```

- Then install it via `composer require milewski/ecs`

- Create a file named `ecs.php` in the root directory of your project with the following content:

```php
<?php

declare(strict_types = 1);

use Milewski\ECS\ValueObject\SetList;
use PhpCsFixer\Fixer\ClassNotation\ClassDefinitionFixer;
use PhpCsFixer\Fixer\ClassNotation\NoBlankLinesAfterClassOpeningFixer;
use PhpCsFixer\Fixer\FunctionNotation\VoidReturnFixer;

return register_fixers([
    NoBlankLinesAfterClassOpeningFixer::class => false,
    ClassDefinitionFixer::class => false,
    VoidReturnFixer::class => true,
])
    ->withParallel()
     ->withSets([ SetList::MILEWSKI ])
    ->withPaths([
        __DIR__,
        __DIR__ . '/app',
        __DIR__ . '/database',
        __DIR__ . '/config',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ]);
```

- Run the `./vendor/bin/ecs check --fix`

## Long calls and method chains

The default preset wraps calls with two or more arguments when their line exceeds 140 characters. Each argument goes on its own line, and parameter names are added when they can be resolved safely. Chains with at least two method calls wrap at 120 characters, putting each method on its own line. Blank lines between chain links are removed, including in short and already multiline chains. Short calls, short chains, and compact argument groups in existing multiline calls are otherwise preserved.

Control structure headers, including `if`, `elseif`, and loop conditions, are excluded from automatic wrapping. Calls and chains within boolean and comparison expressions also stay inline, regardless of length. Standalone chains count the whole line, including assignments and trailing expressions such as `?? new Model()`. Nested chains count their own expression, allowing a long constructor call to expand its arguments while keeping a short chain compact.

`sprintf(...)` keeps its format and values on one line, even above the limit. An enclosing call places that expression on its own argument line, including single-argument calls such as `by(sprintf(...))`. Literal multiline strings are preserved.

Both rules accept a positive integer `max_line_length` option. To change the wrapping threshold in your `ecs.php` configuration:

```php
use Milewski\ECS\Fixers\MethodChainFixer;
use Milewski\ECS\Fixers\MultilineNamedArgumentsFixer;
use Milewski\ECS\ValueObject\SetList;

return register_fixers([
    MethodChainFixer::class => [ 'max_line_length' => 120 ],
    MultilineNamedArgumentsFixer::class => [ 'max_line_length' => 140 ],
])->withSets([ SetList::MILEWSKI ]);
```

Single argument callbacks stay compact. Arguments whose parameter names are unavailable or whose binding depends on variadic parameters or unpacking are wrapped without changing their binding. Builder types assigned outside a conditional remain available inside its branches; typed `when` and `unless` callbacks that return the same builder preserve that type for resolving forwarded methods such as `whereRaw(sql: ..., bindings: ...)`.

Registered closure macros use their declared parameter names. Livewire form assertions also resolve the installed Filament form mixins without booting the application or executing their factories. For example, a two-argument `assertFormFieldExists(...)` call uses `key:` and `form:`; Filament accepts a callback in that second position. Conditional factories and unknown macro signatures remain positional.

## Array layout

Single-line arrays keep bracket padding, such as `[ 28, 84 ]`. When an array contains multiline expressions, its elements start on separate lines after `[` and its closing `]` goes on its own line. This also applies when another rule wraps a constructor call inside the array.

```php
plans: [
    new BillingPlanData(
        priceId: 'local-monthly',
        amountCents: 29900,
    ),
],
```

## Comment policy

The default `SetList::MILEWSKI` preset enables `NoNarrativeCommentsFixer`.
`ecs check` rejects narrative line/block comments and PHPDoc prose; `ecs check --fix`
removes them. Write self-explanatory code and keep explanations in documentation.

Structured PHPDoc remains available for collection generics, array shapes,
templates, model mixins and other type metadata. Descriptions are removed from
mixed PHPDoc blocks. Required PHPStan, Psalm, PHPCS, IDE and coverage directives
remain functional. Invalid typed PHPDoc produces an error instead of deleting
metadata. Comments inside string literals and formatter input fixtures are data.

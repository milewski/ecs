# Installation


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

The default preset wraps calls with two or more arguments when their line exceeds 140 characters. Each argument goes on its own line, and parameter names are added when they can be resolved safely. Existing lists with line breaks before arguments or the closing parenthesis also place each argument on a separate line, even below the length limit. Chains with at least two method calls wrap at 120 characters, putting each method on its own line. Short calls and short chains are preserved.

Multiline arrays place each element on its own line, including arrays containing multiline constructor calls. Inline arrays keep their compact layout. A call with inline arguments and a multiline nested value, such as `save($id, new SnapshotData(...))`, keeps its outer layout unless it exceeds the call wrapping limit.

Control structure headers, including `if`, `elseif`, and loop conditions, are excluded from automatic wrapping. Calls and chains within boolean and comparison expressions also stay inline, regardless of length. Standalone chains count the whole line, including assignments and trailing expressions such as `?? new Model()`. Nested chains count their own expression, allowing a long constructor call to expand its arguments while keeping a short chain compact.

`sprintf(...)` keeps its format and values on one line, even above the limit. An enclosing call places that expression on its own argument line, including single-argument calls such as `by(sprintf(...))`. Existing comments and literal multiline strings are preserved.

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

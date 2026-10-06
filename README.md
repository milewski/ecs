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

## Long calls, arrays, and method chains

The default preset wraps calls with two or more arguments when their line exceeds 140 characters. Each argument goes on its own line, and parameter names are added when they can be resolved safely, including expanded single-argument calls such as `Limit::perMinute(30)->by(key: ...)`. Existing lists with line breaks before arguments or the closing parenthesis also place each argument on a separate line, even below the length limit. Chains with at least two method calls wrap at 120 characters, keeping the receiver and first call together and putting subsequent calls on their own lines. Existing wrapped chains also keep the first call with its receiver. Factory chains start with their factory call, such as `Repository::query()`. Short inline calls and chains are preserved.

Arrays on lines exceeding 140 characters expand with one element per line, including returned model casts. Multiline arrays also place each element on its own line, including arrays containing multiline constructor calls. Short inline arrays keep their compact layout. A call with inline arguments and a multiline nested value, such as `save($id, new SnapshotData(...))`, keeps its outer layout unless it exceeds the call wrapping limit.

Control structure headers, including `if`, `elseif`, and loop conditions, are excluded from automatic wrapping. Calls and chains within boolean and comparison expressions also stay inline, regardless of length. Standalone chains count the whole line, including assignments and trailing expressions such as `?? new Model()`. Nested chains count their own expression, allowing a long constructor call to expand its arguments while keeping a short chain compact.

`sprintf(...)` keeps its format and values on one line, even above the limit. An enclosing call places that expression on its own argument line, including single-argument calls such as `by(sprintf(...))`. Existing comments and literal multiline strings are preserved.

All three rules accept a positive integer `max_line_length` option. To change the wrapping threshold in your `ecs.php` configuration:

```php
use Milewski\ECS\Fixers\MethodChainFixer;
use Milewski\ECS\Fixers\MultilineNamedArgumentsFixer;
use Milewski\ECS\Fixers\PaddedArrayFixer;
use Milewski\ECS\ValueObject\SetList;

return register_fixers([
    MethodChainFixer::class => [ 'max_line_length' => 120 ],
    MultilineNamedArgumentsFixer::class => [ 'max_line_length' => 140 ],
    PaddedArrayFixer::class => [ 'max_line_length' => 140 ],
])->withSets([ SetList::MILEWSKI ]);
```

Callbacks passed inline as a single argument stay compact. Arguments whose parameter names are unavailable or whose binding depends on variadic parameters or unpacking are wrapped without changing their binding. Builder types assigned outside a conditional remain available inside its branches; typed `when` and `unless` callbacks that return the same builder preserve that type for resolving forwarded methods such as `whereRaw(sql: ..., bindings: ...)`.

Laravel's inherited `SessionManager::driver()` uses the session builder's documented return type to resolve store calls such as `put(key: ..., value: ...)`, despite the inherited method's `mixed` return annotation. Unknown driver overrides and unrelated managers are left unresolved.

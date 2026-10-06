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

The default preset wraps calls with two or more arguments when their line exceeds 140 characters. Each argument goes on its own line, and parameter names are added when they can be resolved safely, including expanded single-argument calls such as `Limit::perMinute(30)->by(key: ...)`. Existing lists with line breaks before arguments or the closing parenthesis also place each argument on a separate line, even below the length limit. Chains with at least two method calls wrap at 120 characters. Short variable receivers keep their first call on the same line, such as `$role->whereIn(...)`, including existing wrapped chains and single method calls. A bare variable can stand on its own line when its last character reaches the opening parenthesis of the first method on the next indented line: `$notification` can precede `->body(...)`, while `$aaa` stays with `->body(...)`. The method name, operator, and configured indentation determine this boundary; a tab counts as four spaces. Property receivers such as `$role->permissions` and factory receivers such as `Repository::query()` start the expression, with subsequent method calls on separate lines. Existing partly wrapped chains use the same layout, even below the length limit. Short inline calls and chains are preserved.

Arrays on lines exceeding 140 characters expand with one element per line, including returned model casts. Multiline arrays also place each element on its own line, including arrays containing multiline constructor calls. Short inline arrays keep their compact layout. A call with inline arguments and a multiline nested value, such as `save($id, new SnapshotData(...))`, keeps its outer layout unless it exceeds the call wrapping limit.

Control structure headers, including `if`, `elseif`, and loop conditions, are excluded from automatic wrapping. Calls, chains, and arrays within boolean and comparison expressions also stay inline, regardless of length. Arrays in control headers and `match` subjects or arms stay inline too. Previously expanded expression arrays are compacted when they contain no comments, multiline literals, or block bodies. Arrays in ordinary assignments, returns, and constructor arguments still follow the array wrapping limit. Standalone chains count the whole line, including assignments and trailing expressions such as `?? new Model()`. Nested chains count their own expression, allowing a long constructor call to expand its arguments while keeping a short chain compact.

`sprintf(...)` keeps its format and values on one line, even above the limit. Enclosing method calls place that expression on its own argument line, including single-argument calls such as `by(sprintf(...))`. A free function with only that argument, such as `trim(sprintf(...))`, stays inline when the whole line fits within 140 characters, including indentation, the named argument prefix, and any trailing expression. Previously expanded short wrappers are compacted too. Existing comments and literal multiline strings are preserved.

Pest `test(...)` declarations keep the description and callback opening on the same line regardless of length. Previously expanded declarations are compacted, and `description:` and `closure:` names are removed when they match their positional order. Callback bodies, comments, and dataset chains retain their normal formatting. Methods and explicitly namespaced functions such as `Custom\test()` follow the ordinary call rules.

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

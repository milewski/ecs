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

The default preset wraps calls with two or more arguments when their line exceeds 140 characters. Each argument goes on its own line, and parameter names are added when they can be resolved safely. Chains with at least two method calls wrap at 120 characters, putting each method on its own line. Once a chain has a link on a new line, every continuation goes on its own line regardless of line length, keeping an existing initial receiver call together. Blank lines between chain links are removed, including in short and already multiline chains. Short calls, single-line short chains, and compact argument groups in existing multiline calls are otherwise preserved. Multiline arguments alone do not force a short chain to expand.

Control structure headers, including `if`, `elseif`, and loop conditions, are excluded from automatic wrapping. Calls and chains within boolean and comparison expressions also stay inline, regardless of length. Standalone chains count the whole line, including assignments and trailing expressions such as `?? new Model()`. Nested chains count their own expression, allowing a long constructor call to expand its arguments while keeping a short chain compact.

`sprintf(...)` keeps its format and values on one line, even above the limit. An enclosing call places that expression on its own argument line, including single-argument calls such as `by(sprintf(...))`. Literal multiline strings are preserved.

Pest's `test(...)` and `it(...)` keep the description and closure opening on the same line, regardless of length. Previously expanded calls return to `test('description', function (): void { ... });`, removing `description:` and `closure:` when their order matches the positional parameters. Closure bodies keep their normal formatting. Calls with unpacked arguments, reordered or unknown argument names, or comments between arguments are preserved.

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

## Explicit Eloquent attribute assignment

In Laravel projects, the default `SetList::MILEWSKI` preset enables
`ForbidLaravelMassAssignmentSniff`. Assign every attribute individually and persist
the model with `save()`:

```php
$user = new User();
$user->name = $name;
$user->email = $email;
$user->save();
```

The rule rejects model mass assignment even when an array contains explicit keys
or validated input. This includes `fill`, `forceFill`, `update` on a model
instance, model constructor attributes, and methods such as `create`,
`firstOrCreate`, `updateOrCreate` and relationship creation that populate model
attributes internally. Quiet, forced and `updateOrFail` variants are also
rejected. `unguard`, `unguarded`, and runtime fillable/guard mutators are forbidden.

Query-builder writes are allowed without `toBase()`. This includes Eloquent and
relationship query updates, bulk inserts and upserts:

```php
User::where('active', true)->update(['status' => 'verified']);
User::query()->insert($rows);
User::upsert($rows, ['email'], ['name']);
$user->posts()->whereNull('published_at')->update(['published_at' => now()]);
```

These operations write SQL columns without filling model instances. Query
updates retain Eloquent's timestamp handling and execute as one statement. The
rule distinguishes queries from retrieved models: `User::where(...)->first()->update($attributes)`
is still rejected. Choose the written columns explicitly and validate and
authorize their values; allowing query writes does not add mass-assignment
protection to them.

Models must retain full guarding: omit `$guarded` to inherit Laravel's default,
or declare `protected $guarded = ['*'];`. Empty or partial guards, including
`protected $guarded = [];`, nonempty `$fillable`, and enabling `$unguarded` are
reported. Laravel's `Unguarded`, `Fillable`, and `Guarded` attributes follow the
same policy. `ecs check --fix` reports these errors without rewriting persistence
logic.

Laravel is detected through a loaded application/helpers or an ancestor containing
both `artisan` and `bootstrap/app.php`. Receivers are resolved from imports, local
model inheritance, autoloadable classes, native parameter/property/return types,
assignments, and Eloquent chains. Unrelated APIs such as `Schema::create()` and
factory creation remain available. Dynamically named calls and receivers whose
model type cannot be resolved require additional static analysis or code review.

## Error handling

The default `SetList::MILEWSKI` preset forbids the `@` error suppression operator,
including `@file_get_contents(...)`. Handle errors explicitly instead.
Both `ecs check` and `ecs check --fix` report an error; this rule does not
automatically change error handling. At signs inside strings and comments are
unaffected.

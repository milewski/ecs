<?php

declare(strict_types = 1);

namespace Milewski\ECS\PhpCsFixer;

use PhpCsFixer\Fixer\FunctionNotation\CombineNestedDirnameFixer;
use PhpCsFixer\Fixer\FunctionNotation\DateTimeCreateFromFormatCallFixer;
use PhpCsFixer\Fixer\FunctionNotation\FopenFlagOrderFixer;
use PhpCsFixer\Fixer\FunctionNotation\FopenFlagsFixer;
use PhpCsFixer\Fixer\FunctionNotation\FunctionDeclarationFixer;
use PhpCsFixer\Fixer\FunctionNotation\FunctionTypehintSpaceFixer;
use PhpCsFixer\Fixer\FunctionNotation\ImplodeCallFixer;
use PhpCsFixer\Fixer\FunctionNotation\LambdaNotUsedImportFixer;
use PhpCsFixer\Fixer\FunctionNotation\MethodArgumentSpaceFixer;
use PhpCsFixer\Fixer\FunctionNotation\NativeFunctionInvocationFixer;
use PhpCsFixer\Fixer\FunctionNotation\NoSpacesAfterFunctionNameFixer;
use PhpCsFixer\Fixer\FunctionNotation\NoUnreachableDefaultArgumentValueFixer;
use PhpCsFixer\Fixer\FunctionNotation\NoUselessSprintfFixer;
use PhpCsFixer\Fixer\FunctionNotation\NullableTypeDeclarationForDefaultNullValueFixer;
use PhpCsFixer\Fixer\FunctionNotation\PhpdocToParamTypeFixer;
use PhpCsFixer\Fixer\FunctionNotation\PhpdocToPropertyTypeFixer;
use PhpCsFixer\Fixer\FunctionNotation\PhpdocToReturnTypeFixer;
use PhpCsFixer\Fixer\FunctionNotation\RegularCallableCallFixer;
use PhpCsFixer\Fixer\FunctionNotation\ReturnTypeDeclarationFixer;
use PhpCsFixer\Fixer\FunctionNotation\SingleLineThrowFixer;
use PhpCsFixer\Fixer\FunctionNotation\StaticLambdaFixer;
use PhpCsFixer\Fixer\FunctionNotation\UseArrowFunctionsFixer;
use PhpCsFixer\Fixer\FunctionNotation\VoidReturnFixer;

return register_fixers([
    CombineNestedDirnameFixer::class => true,
    DateTimeCreateFromFormatCallFixer::class => true,
    FopenFlagOrderFixer::class => true,
    FopenFlagsFixer::class => true,
    FunctionDeclarationFixer::class => true,
    FunctionTypehintSpaceFixer::class => true,
    ImplodeCallFixer::class => true,
    LambdaNotUsedImportFixer::class => true,
    MethodArgumentSpaceFixer::class => [ 'on_multiline' => 'ignore' ],
    NativeFunctionInvocationFixer::class => false,
    NoSpacesAfterFunctionNameFixer::class => true,
    NoUnreachableDefaultArgumentValueFixer::class => true,
    NoUselessSprintfFixer::class => true,
    NullableTypeDeclarationForDefaultNullValueFixer::class => true,
    PhpdocToParamTypeFixer::class => false,
    PhpdocToPropertyTypeFixer::class => false,
    PhpdocToReturnTypeFixer::class => false,

    RegularCallableCallFixer::class => false,
    ReturnTypeDeclarationFixer::class => true,
    SingleLineThrowFixer::class => false,
    StaticLambdaFixer::class => false,
    UseArrowFunctionsFixer::class => false,
    VoidReturnFixer::class => false,
]);

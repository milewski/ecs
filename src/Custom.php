<?php

declare(strict_types = 1);

namespace Milewski\ECS;

use Milewski\ECS\Fixers\AutoImportClassesFixer;
use Milewski\ECS\Fixers\ClassOpeningBracketFixer;
use Milewski\ECS\Fixers\ConstructorBracesFixer;
use Milewski\ECS\Fixers\DescriptiveVariableNameFixer;
use Milewski\ECS\Fixers\FunctionParameterLayoutFixer;
use Milewski\ECS\Fixers\LaravelEmptyToBlankFixer;
use Milewski\ECS\Fixers\MethodChainFixer;
use Milewski\ECS\Fixers\MultilineNamedArgumentsFixer;
use Milewski\ECS\Fixers\NoPointlessMixedPhpdocFixer;
use Milewski\ECS\Fixers\PaddedArrayFixer;
use Milewski\ECS\Fixers\PaddedBlockFixer;
use Milewski\ECS\Fixers\PaddedDocblockFixer;
use Milewski\ECS\Fixers\PaddedMultilineStatementFixer;
use Milewski\ECS\Fixers\SnakeCaseGlobalFunctionNameFixer;
use Milewski\ECS\Fixers\StatementGroupingFixer;
use Milewski\ECS\Fixers\TraitUseSpacingFixer;
use Milewski\ECS\Sniffs\ForbidSwitchStatementSniff;
use Milewski\ECS\Sniffs\RequireParameterTypeSniff;

return register_fixers(fixers: [
    AutoImportClassesFixer::class => true,
    PaddedArrayFixer::class => true,
    PaddedBlockFixer::class => true,
    PaddedDocblockFixer::class => true,
    PaddedMultilineStatementFixer::class => true,
    StatementGroupingFixer::class => true,
    TraitUseSpacingFixer::class => true,
    ClassOpeningBracketFixer::class => true,
    ConstructorBracesFixer::class => true,
    DescriptiveVariableNameFixer::class => true,
    FunctionParameterLayoutFixer::class => true,
    MethodChainFixer::class => true,
    MultilineNamedArgumentsFixer::class => true,
    NoPointlessMixedPhpdocFixer::class => true,
    SnakeCaseGlobalFunctionNameFixer::class => true,
    ForbidSwitchStatementSniff::class => true,
    RequireParameterTypeSniff::class => true,
    LaravelEmptyToBlankFixer::class => true,
])->withSets([
    __DIR__ . '/PhpCsFixer/Alias.php',
    __DIR__ . '/PhpCsFixer/ArrayNotation.php',
    __DIR__ . '/PhpCsFixer/Basic.php',
    __DIR__ . '/PhpCsFixer/Casing.php',
    __DIR__ . '/PhpCsFixer/CastNotation.php',
    __DIR__ . '/PhpCsFixer/ClassNotation.php',
    __DIR__ . '/PhpCsFixer/Comment.php',
    __DIR__ . '/PhpCsFixer/ConstNotation.php',
    __DIR__ . '/PhpCsFixer/ControlStructure.php',
    __DIR__ . '/PhpCsFixer/FunctionNotation.php',
    __DIR__ . '/PhpCsFixer/Import.php',
    __DIR__ . '/PhpCsFixer/LanguageConstruct.php',
    __DIR__ . '/PhpCsFixer/ListNotation.php',
    __DIR__ . '/PhpCsFixer/NamespaceNotation.php',
    __DIR__ . '/PhpCsFixer/Naming.php',
    __DIR__ . '/PhpCsFixer/Operator.php',
    __DIR__ . '/PhpCsFixer/Phpdoc.php',
    __DIR__ . '/PhpCsFixer/PhpTag.php',
    __DIR__ . '/PhpCsFixer/ReturnNotation.php',
    __DIR__ . '/PhpCsFixer/Semicolon.php',
    __DIR__ . '/PhpCsFixer/Strict.php',
    __DIR__ . '/PhpCsFixer/StringNotation.php',
    __DIR__ . '/PhpCsFixer/Whitespace.php',
]);

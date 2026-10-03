<?php

declare(strict_types = 1);

namespace DigitalCreative\ECS;

use DigitalCreative\ECS\Fixers\AutoImportClassesFixer;
use DigitalCreative\ECS\Fixers\ClassOpeningBracketFixer;
use DigitalCreative\ECS\Fixers\ConstructorBracesFixer;
use DigitalCreative\ECS\Fixers\DescriptiveVariableNameFixer;
use DigitalCreative\ECS\Fixers\FunctionParameterLayoutFixer;
use DigitalCreative\ECS\Fixers\LaravelEmptyToBlankFixer;
use DigitalCreative\ECS\Fixers\MethodChainFixer;
use DigitalCreative\ECS\Fixers\MultilineNamedArgumentsFixer;
use DigitalCreative\ECS\Fixers\NoPointlessMixedPhpdocFixer;
use DigitalCreative\ECS\Fixers\PaddedArrayFixer;
use DigitalCreative\ECS\Fixers\PaddedBlockFixer;
use DigitalCreative\ECS\Fixers\PaddedDocblockFixer;
use DigitalCreative\ECS\Fixers\PaddedMultilineStatementFixer;
use DigitalCreative\ECS\Fixers\SnakeCaseGlobalFunctionNameFixer;
use DigitalCreative\ECS\Fixers\StatementGroupingFixer;
use DigitalCreative\ECS\Fixers\TraitUseSpacingFixer;
use DigitalCreative\ECS\Sniffs\ForbidSwitchStatementSniff;
use DigitalCreative\ECS\Sniffs\RequireParameterTypeSniff;

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

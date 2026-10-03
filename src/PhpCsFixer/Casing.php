<?php

declare(strict_types = 1);

namespace Milewski\ECS\PhpCsFixer;

use PhpCsFixer\Fixer\Casing\ClassReferenceNameCasingFixer;
use PhpCsFixer\Fixer\Casing\ConstantCaseFixer;
use PhpCsFixer\Fixer\Casing\IntegerLiteralCaseFixer;
use PhpCsFixer\Fixer\Casing\LowercaseKeywordsFixer;
use PhpCsFixer\Fixer\Casing\LowercaseStaticReferenceFixer;
use PhpCsFixer\Fixer\Casing\MagicConstantCasingFixer;
use PhpCsFixer\Fixer\Casing\MagicMethodCasingFixer;
use PhpCsFixer\Fixer\Casing\NativeFunctionCasingFixer;
use PhpCsFixer\Fixer\Casing\NativeFunctionTypeDeclarationCasingFixer;

return register_fixers([
    ClassReferenceNameCasingFixer::class => true,
    ConstantCaseFixer::class => true,
    IntegerLiteralCaseFixer::class => false,
    LowercaseKeywordsFixer::class => true,
    LowercaseStaticReferenceFixer::class => true,
    MagicConstantCasingFixer::class => true,
    MagicMethodCasingFixer::class => true,
    NativeFunctionCasingFixer::class => true,
    NativeFunctionTypeDeclarationCasingFixer::class => true,
]);

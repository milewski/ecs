<?php

declare(strict_types = 1);

namespace Milewski\ECS\PhpCsFixer;

use PhpCsFixer\Fixer\Phpdoc\AlignMultilineCommentFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocTagRenameFixer;
use PhpCsFixer\Fixer\Phpdoc\NoBlankLinesAfterPhpdocFixer;
use PhpCsFixer\Fixer\Phpdoc\NoEmptyPhpdocFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocAddMissingParamAnnotationFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocAlignFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocAnnotationWithoutDotFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocIndentFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocInlineTagNormalizerFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocLineSpanFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoAccessFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoAliasTagFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoEmptyReturnFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoPackageFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoUselessInheritdocFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocOrderByValueFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocOrderFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocReturnSelfReferenceFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocScalarFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocSeparationFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocSingleLineVarSpacingFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocSummaryFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTagCasingFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTagTypeFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocToCommentFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTrimConsecutiveBlankLineSeparationFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTrimFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTypesFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTypesOrderFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocVarAnnotationCorrectOrderFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocVarWithoutNameFixer;

return register_fixers([
    AlignMultilineCommentFixer::class => [ 'comment_type' => 'all_multiline' ],
    GeneralPhpdocAnnotationRemoveFixer::class => true,
    GeneralPhpdocTagRenameFixer::class => true,
    NoBlankLinesAfterPhpdocFixer::class => true,
    NoEmptyPhpdocFixer::class => true,
    NoSuperfluousPhpdocTagsFixer::class => true,
    PhpdocAddMissingParamAnnotationFixer::class => false,
    PhpdocAlignFixer::class => [ 'align' => 'left' ],
    PhpdocAnnotationWithoutDotFixer::class => false,
    PhpdocIndentFixer::class => true,
    PhpdocInlineTagNormalizerFixer::class => true,
    PhpdocLineSpanFixer::class => [ 'other' => 'multi' ],
    PhpdocNoAccessFixer::class => true,
    PhpdocNoAliasTagFixer::class => true,
    PhpdocNoEmptyReturnFixer::class => true,
    PhpdocNoPackageFixer::class => true,
    PhpdocNoUselessInheritdocFixer::class => true,
    PhpdocOrderByValueFixer::class => true,
    PhpdocOrderFixer::class => true,
    PhpdocReturnSelfReferenceFixer::class => false,
    PhpdocScalarFixer::class => true,
    PhpdocSeparationFixer::class => false,
    PhpdocSingleLineVarSpacingFixer::class => true,
    PhpdocSummaryFixer::class => false,
    PhpdocTagCasingFixer::class => true,
    PhpdocTagTypeFixer::class => true,
    PhpdocToCommentFixer::class => false,
    PhpdocTrimConsecutiveBlankLineSeparationFixer::class => true,
    PhpdocTrimFixer::class => true,
    PhpdocTypesFixer::class => true,
    PhpdocTypesOrderFixer::class => [ 'null_adjustment' => 'always_last' ],
    PhpdocVarAnnotationCorrectOrderFixer::class => true,
    PhpdocVarWithoutNameFixer::class => true,
]);

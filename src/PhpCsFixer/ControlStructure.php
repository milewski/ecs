<?php

declare(strict_types = 1);

namespace Milewski\ECS\PhpCsFixer;

use PhpCsFixer\Fixer\ControlStructure\ControlStructureBracesFixer;
use PhpCsFixer\Fixer\ControlStructure\ControlStructureContinuationPositionFixer;
use PhpCsFixer\Fixer\ControlStructure\ElseifFixer;
use PhpCsFixer\Fixer\ControlStructure\EmptyLoopBodyFixer;
use PhpCsFixer\Fixer\ControlStructure\EmptyLoopConditionFixer;
use PhpCsFixer\Fixer\ControlStructure\IncludeFixer;
use PhpCsFixer\Fixer\ControlStructure\NoAlternativeSyntaxFixer;
use PhpCsFixer\Fixer\ControlStructure\NoBreakCommentFixer;
use PhpCsFixer\Fixer\ControlStructure\NoSuperfluousElseifFixer;
use PhpCsFixer\Fixer\ControlStructure\NoTrailingCommaInListCallFixer;
use PhpCsFixer\Fixer\ControlStructure\NoUnneededControlParenthesesFixer;
use PhpCsFixer\Fixer\ControlStructure\NoUnneededCurlyBracesFixer;
use PhpCsFixer\Fixer\ControlStructure\NoUselessElseFixer;
use PhpCsFixer\Fixer\ControlStructure\SimplifiedIfReturnFixer;
use PhpCsFixer\Fixer\ControlStructure\SwitchCaseSemicolonToColonFixer;
use PhpCsFixer\Fixer\ControlStructure\SwitchCaseSpaceFixer;
use PhpCsFixer\Fixer\ControlStructure\SwitchContinueToBreakFixer;
use PhpCsFixer\Fixer\ControlStructure\TrailingCommaInMultilineFixer;
use PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer;

return register_fixers([
    ControlStructureBracesFixer::class => true,
    ControlStructureContinuationPositionFixer::class => true,
    ElseifFixer::class => false,
    EmptyLoopBodyFixer::class => [ 'style' => 'braces' ],
    EmptyLoopConditionFixer::class => true,
    IncludeFixer::class => true,
    NoAlternativeSyntaxFixer::class => true,
    NoBreakCommentFixer::class => false,
    NoSuperfluousElseifFixer::class => true,
    //        NoTrailingCommaInListCallFixer::class => true,
    NoUnneededControlParenthesesFixer::class => true,
    NoUnneededCurlyBracesFixer::class => true,
    NoUselessElseFixer::class => true,
    SimplifiedIfReturnFixer::class => false,
    SwitchCaseSemicolonToColonFixer::class => true,
    SwitchCaseSpaceFixer::class => true,
    SwitchContinueToBreakFixer::class => true,
    TrailingCommaInMultilineFixer::class => [ 'elements' => [ 'arguments', 'arrays', 'match', 'parameters' ] ],
    YodaStyleFixer::class => false,
]);

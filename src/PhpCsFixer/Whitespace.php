<?php

declare(strict_types = 1);

namespace Milewski\ECS\PhpCsFixer;

use PhpCsFixer\Fixer\Whitespace\ArrayIndentationFixer;
use PhpCsFixer\Fixer\Whitespace\BlankLineBeforeStatementFixer;
use PhpCsFixer\Fixer\Whitespace\BlankLineBetweenImportGroupsFixer;
use PhpCsFixer\Fixer\Whitespace\CompactNullableTypehintFixer;
use PhpCsFixer\Fixer\Whitespace\HeredocIndentationFixer;
use PhpCsFixer\Fixer\Whitespace\IndentationTypeFixer;
use PhpCsFixer\Fixer\Whitespace\LineEndingFixer;
use PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer;
use PhpCsFixer\Fixer\Whitespace\NoExtraBlankLinesFixer;
use PhpCsFixer\Fixer\Whitespace\NoSpacesAroundOffsetFixer;
use PhpCsFixer\Fixer\Whitespace\NoSpacesInsideParenthesisFixer;
use PhpCsFixer\Fixer\Whitespace\NoTrailingWhitespaceFixer;
use PhpCsFixer\Fixer\Whitespace\NoWhitespaceInBlankLineFixer;
use PhpCsFixer\Fixer\Whitespace\SingleBlankLineAtEofFixer;
use PhpCsFixer\Fixer\Whitespace\StatementIndentationFixer;
use PhpCsFixer\Fixer\Whitespace\TypesSpacesFixer;

return register_fixers([
    ArrayIndentationFixer::class => true,
    BlankLineBeforeStatementFixer::class => [ 'statements' => [ 'do', 'for', 'foreach', 'if', 'return', 'switch', 'try', 'while', 'yield', 'yield_from' ] ],
    BlankLineBetweenImportGroupsFixer::class => true,
    CompactNullableTypehintFixer::class => true,
    HeredocIndentationFixer::class => [ 'indentation' => 'same_as_start' ],
    IndentationTypeFixer::class => true,
    LineEndingFixer::class => true,
    MethodChainingIndentationFixer::class => true,
    NoExtraBlankLinesFixer::class => [ 'tokens' => [ 'extra', 'use' ] ],
    NoSpacesAroundOffsetFixer::class => [ 'positions' => [ 'outside' ] ],
    NoSpacesInsideParenthesisFixer::class => true,
    NoTrailingWhitespaceFixer::class => true,
    NoWhitespaceInBlankLineFixer::class => true,
    SingleBlankLineAtEofFixer::class => true,
    StatementIndentationFixer::class => true,
    TypesSpacesFixer::class => [ 'space' => 'none' ],
]);

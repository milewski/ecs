<?php

declare(strict_types = 1);

namespace Milewski\ECS\PhpCsFixer;

use PhpCsFixer\Fixer\Semicolon\MultilineWhitespaceBeforeSemicolonsFixer;
use PhpCsFixer\Fixer\Semicolon\NoEmptyStatementFixer;
use PhpCsFixer\Fixer\Semicolon\NoSinglelineWhitespaceBeforeSemicolonsFixer;
use PhpCsFixer\Fixer\Semicolon\SemicolonAfterInstructionFixer;
use PhpCsFixer\Fixer\Semicolon\SpaceAfterSemicolonFixer;

return register_fixers([
    MultilineWhitespaceBeforeSemicolonsFixer::class => true,
    NoEmptyStatementFixer::class => true,
    NoSinglelineWhitespaceBeforeSemicolonsFixer::class => true,
    SemicolonAfterInstructionFixer::class => true,
    SpaceAfterSemicolonFixer::class => true,
]);

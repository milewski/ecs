<?php

declare(strict_types = 1);

namespace Milewski\ECS\PhpCsFixer;

use PhpCsFixer\Fixer\Basic\BracesFixer;
use PhpCsFixer\Fixer\Basic\CurlyBracesPositionFixer;
use PhpCsFixer\Fixer\Basic\EncodingFixer;
use PhpCsFixer\Fixer\Basic\NoMultipleStatementsPerLineFixer;
use PhpCsFixer\Fixer\Basic\NonPrintableCharacterFixer;
use PhpCsFixer\Fixer\Basic\NoTrailingCommaInSinglelineFixer;
use PhpCsFixer\Fixer\Basic\OctalNotationFixer;
use PhpCsFixer\Fixer\Basic\PsrAutoloadingFixer;

return register_fixers([
    BracesFixer::class => false,
    CurlyBracesPositionFixer::class => false,
    EncodingFixer::class => true,
    NoMultipleStatementsPerLineFixer::class => true,
    NonPrintableCharacterFixer::class => true,
    NoTrailingCommaInSinglelineFixer::class => true,
    OctalNotationFixer::class => true,
    PsrAutoloadingFixer::class => true,
]);

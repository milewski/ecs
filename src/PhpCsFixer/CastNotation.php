<?php

declare(strict_types = 1);

namespace Milewski\ECS\PhpCsFixer;

use PhpCsFixer\Fixer\CastNotation\CastSpacesFixer;
use PhpCsFixer\Fixer\CastNotation\LowercaseCastFixer;
use PhpCsFixer\Fixer\CastNotation\ModernizeTypesCastingFixer;
use PhpCsFixer\Fixer\CastNotation\NoShortBoolCastFixer;
use PhpCsFixer\Fixer\CastNotation\NoUnsetCastFixer;
use PhpCsFixer\Fixer\CastNotation\ShortScalarCastFixer;

return register_fixers([
    CastSpacesFixer::class => true,
    LowercaseCastFixer::class => true,
    ModernizeTypesCastingFixer::class => true,
    NoShortBoolCastFixer::class => true,
    NoUnsetCastFixer::class => true,
    ShortScalarCastFixer::class => true,
]);

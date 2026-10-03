<?php

declare(strict_types = 1);

namespace Milewski\ECS\PhpCsFixer;

use PhpCsFixer\Fixer\ReturnNotation\NoUselessReturnFixer;
use PhpCsFixer\Fixer\ReturnNotation\ReturnAssignmentFixer;
use PhpCsFixer\Fixer\ReturnNotation\SimplifiedNullReturnFixer;

return register_fixers([
    NoUselessReturnFixer::class => true,
    ReturnAssignmentFixer::class => true,
    SimplifiedNullReturnFixer::class => false,
]);

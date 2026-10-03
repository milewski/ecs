<?php

declare(strict_types = 1);

namespace Milewski\ECS\PhpCsFixer;

use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\Strict\StrictComparisonFixer;
use PhpCsFixer\Fixer\Strict\StrictParamFixer;

return register_fixers([
    DeclareStrictTypesFixer::class => true,
    StrictComparisonFixer::class => true,
    StrictParamFixer::class => false,
]);

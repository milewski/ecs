<?php

declare(strict_types = 1);

namespace Milewski\ECS\PhpCsFixer;

use PhpCsFixer\Fixer\ListNotation\ListSyntaxFixer;

return register_fixers([
    ListSyntaxFixer::class => true,
]);

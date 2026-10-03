<?php

declare(strict_types = 1);

namespace Milewski\ECS\PhpCsFixer;

use PhpCsFixer\Fixer\Naming\NoHomoglyphNamesFixer;

return register_fixers([
    NoHomoglyphNamesFixer::class => true,
]);

<?php

declare(strict_types = 1);

namespace Milewski\ECS\PhpCsFixer;

use PhpCsFixer\Fixer\ConstantNotation\NativeConstantInvocationFixer;

return register_fixers([
    NativeConstantInvocationFixer::class => false,
]);

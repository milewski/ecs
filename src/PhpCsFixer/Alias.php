<?php

declare(strict_types = 1);

namespace Milewski\ECS\PhpCsFixer;

use PhpCsFixer\Fixer\Alias\ArrayPushFixer;
use PhpCsFixer\Fixer\Alias\BacktickToShellExecFixer;
use PhpCsFixer\Fixer\Alias\EregToPregFixer;
use PhpCsFixer\Fixer\Alias\MbStrFunctionsFixer;
use PhpCsFixer\Fixer\Alias\ModernizeStrposFixer;
use PhpCsFixer\Fixer\Alias\NoAliasFunctionsFixer;
use PhpCsFixer\Fixer\Alias\NoAliasLanguageConstructCallFixer;
use PhpCsFixer\Fixer\Alias\NoMixedEchoPrintFixer;
use PhpCsFixer\Fixer\Alias\PowToExponentiationFixer;
use PhpCsFixer\Fixer\Alias\RandomApiMigrationFixer;
use PhpCsFixer\Fixer\Alias\SetTypeToCastFixer;

return register_fixers([
    ArrayPushFixer::class => true,
    BacktickToShellExecFixer::class => false,
    EregToPregFixer::class => true,
    MbStrFunctionsFixer::class => false,
    ModernizeStrposFixer::class => false,
    NoAliasFunctionsFixer::class => true,
    NoAliasLanguageConstructCallFixer::class => true,
    NoMixedEchoPrintFixer::class => true,
    PowToExponentiationFixer::class => true,
    RandomApiMigrationFixer::class => true,
    SetTypeToCastFixer::class => true,
]);

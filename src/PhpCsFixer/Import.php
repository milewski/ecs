<?php

declare(strict_types = 1);

namespace Milewski\ECS\PhpCsFixer;

use PhpCsFixer\Fixer\Import\FullyQualifiedStrictTypesFixer;
use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;
use PhpCsFixer\Fixer\Import\GroupImportFixer;
use PhpCsFixer\Fixer\Import\NoLeadingImportSlashFixer;
use PhpCsFixer\Fixer\Import\NoUnneededImportAliasFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\Import\SingleImportPerStatementFixer;
use PhpCsFixer\Fixer\Import\SingleLineAfterImportsFixer;

return register_fixers([
    FullyQualifiedStrictTypesFixer::class => true,
    GlobalNamespaceImportFixer::class => [ 'import_classes' => true, 'import_constants' => false, 'import_functions' => false ],
    GroupImportFixer::class => false,
    NoLeadingImportSlashFixer::class => true,
    NoUnneededImportAliasFixer::class => true,
    NoUnusedImportsFixer::class => true,
    OrderedImportsFixer::class => true,
    SingleImportPerStatementFixer::class => true,
    SingleLineAfterImportsFixer::class => true,
]);

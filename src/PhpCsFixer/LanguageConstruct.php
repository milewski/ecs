<?php

declare(strict_types = 1);

namespace Milewski\ECS\PhpCsFixer;

use PhpCsFixer\Fixer\LanguageConstruct\CombineConsecutiveIssetsFixer;
use PhpCsFixer\Fixer\LanguageConstruct\CombineConsecutiveUnsetsFixer;
use PhpCsFixer\Fixer\LanguageConstruct\DeclareEqualNormalizeFixer;
use PhpCsFixer\Fixer\LanguageConstruct\DeclareParenthesesFixer;
use PhpCsFixer\Fixer\LanguageConstruct\DirConstantFixer;
use PhpCsFixer\Fixer\LanguageConstruct\ErrorSuppressionFixer;
use PhpCsFixer\Fixer\LanguageConstruct\ExplicitIndirectVariableFixer;
use PhpCsFixer\Fixer\LanguageConstruct\FunctionToConstantFixer;
use PhpCsFixer\Fixer\LanguageConstruct\GetClassToClassKeywordFixer;
use PhpCsFixer\Fixer\LanguageConstruct\IsNullFixer;
use PhpCsFixer\Fixer\LanguageConstruct\NoUnsetOnPropertyFixer;
use PhpCsFixer\Fixer\LanguageConstruct\SingleSpaceAfterConstructFixer;

return register_fixers([
    CombineConsecutiveIssetsFixer::class => true,
    CombineConsecutiveUnsetsFixer::class => true,
    DeclareEqualNormalizeFixer::class => [ 'space' => 'single' ],
    DeclareParenthesesFixer::class => true,
    DirConstantFixer::class => true,
    ErrorSuppressionFixer::class => false,
    ExplicitIndirectVariableFixer::class => true,
    FunctionToConstantFixer::class => true,
    GetClassToClassKeywordFixer::class => true,
    IsNullFixer::class => false,
    NoUnsetOnPropertyFixer::class => true,
    SingleSpaceAfterConstructFixer::class => true,
]);

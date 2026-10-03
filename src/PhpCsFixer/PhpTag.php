<?php

declare(strict_types = 1);

namespace Milewski\ECS\PhpCsFixer;

use PhpCsFixer\Fixer\PhpTag\BlankLineAfterOpeningTagFixer;
use PhpCsFixer\Fixer\PhpTag\EchoTagSyntaxFixer;
use PhpCsFixer\Fixer\PhpTag\FullOpeningTagFixer;
use PhpCsFixer\Fixer\PhpTag\LinebreakAfterOpeningTagFixer;
use PhpCsFixer\Fixer\PhpTag\NoClosingTagFixer;

return register_fixers([
    BlankLineAfterOpeningTagFixer::class => true,
    EchoTagSyntaxFixer::class => true,
    FullOpeningTagFixer::class => true,
    LinebreakAfterOpeningTagFixer::class => true,
    NoClosingTagFixer::class => true,
]);

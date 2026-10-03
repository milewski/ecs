<?php

declare(strict_types = 1);

use Milewski\ECS\Fixers\FunctionParameterLayoutFixer;

return register_fixers([
    FunctionParameterLayoutFixer::class => true,
]);

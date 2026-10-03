<?php

declare(strict_types = 1);

use Milewski\ECS\Sniffs\RequireParameterTypeSniff;

return register_fixers([
    RequireParameterTypeSniff::class => true,
]);

<?php

declare(strict_types = 1);

use Milewski\ECS\Sniffs\ForbidSwitchStatementSniff;

return register_fixers([
    ForbidSwitchStatementSniff::class => true,
]);

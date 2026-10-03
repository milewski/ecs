<?php

declare(strict_types = 1);

use Milewski\ECS\Fixers\PaddedBlockFixer;

return register_fixers([
    PaddedBlockFixer::class => true,
]);

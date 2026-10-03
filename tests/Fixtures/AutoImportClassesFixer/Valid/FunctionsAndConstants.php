<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\AutoImportClassesFixer;

function values(): array
{
    return [
        \Vendor\Package\value(),
        \Vendor\Package\VALUE,
    ];
}

<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Support;

use PHPUnit\Framework\TestCase;
use Symplify\EasyCodingStandard\Config\ECSConfig;

abstract class FixerTestCase extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Initialize ECS's scoped autoloader before using PHP-CS-Fixer classes directly.
        ECSConfig::configure();
    }
}

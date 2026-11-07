<?php

declare(strict_types = 1);

namespace Milewski\ECS\TokenAnalyzer;

use SplFileInfo;

final class LaravelProjectDetector
{
    public function supports(SplFileInfo $file): bool
    {
        if (class_exists('Illuminate\\Foundation\\Application')) {
            return true;
        }

        if (function_exists('blank')) {

            if (function_exists('filled')) {
                return true;
            }

        }

        $directory = dirname($file->getRealPath() ?: $file->getPathname());

        while (true) {

            if (is_file($directory . '/artisan')) {

                if (is_file($directory . '/bootstrap/app.php')) {
                    return true;
                }

            }

            $parent = dirname($directory);

            if ($parent === $directory) {
                return false;
            }

            $directory = $parent;

        }
    }
}

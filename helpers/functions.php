<?php

declare(strict_types = 1);

use PhpParser\Lexer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\Configuration\ECSConfigBuilder;

// Load PHP Parser's integer token definitions before CodeSniffer emulates PHP 8.5 tokens as strings on PHP 8.4.
class_exists(Lexer::class);

function register_fixers(array $fixers): ECSConfigBuilder
{
    $config = ECSConfig::configure();
    $rules = [];
    $skips = [];

    foreach ($fixers as $fixer => $options) {

        if (is_bool($options)) {

            if ($options) {

                $rules[] = $fixer;

            } else {

                $skips[] = $fixer;

            }

        }

        if (is_array($options)) {
            $config->withConfiguredRule($fixer, $options);
        }

    }

    return $config->withRules($rules)->withSkip($skips);
}

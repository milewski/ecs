<?php

declare(strict_types = 1);

namespace Milewski\ECS\TokenAnalyzer;

use PhpCsFixer\Tokenizer\Tokens;

final class LineLengthAnalyzer
{
    public static function maximumLength(Tokens $tokens, int $start, int $end): int
    {
        $length = 0;

        for ($index = $start - 1; $index >= 0; $index--) {

            $lines = preg_split('/\R/', $tokens[ $index ]->getContent());
            $length += strlen($lines[ count($lines) - 1 ]);

            if (count($lines) > 1) {
                break;
            }

        }

        $maximumLength = $length;

        for ($index = $start; $index < $tokens->count(); $index++) {

            foreach (preg_split('/\R/', $tokens[ $index ]->getContent()) as $lineIndex => $line) {

                if ($lineIndex > 0) {

                    $maximumLength = max($maximumLength, $length);

                    if ($index >= $end) {
                        return $maximumLength;
                    }

                    $length = 0;

                }

                $length += strlen($line);

            }

        }

        return max($maximumLength, $length);
    }
}

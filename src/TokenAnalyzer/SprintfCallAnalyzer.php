<?php

declare(strict_types = 1);

namespace Milewski\ECS\TokenAnalyzer;

use PhpCsFixer\Tokenizer\Tokens;

final class SprintfCallAnalyzer
{
    public static function findEnd(Tokens $tokens, int $index): ?int
    {
        if ($tokens[ $index ]->equals('(') === false) {
            return null;
        }

        $name = $tokens->getPrevMeaningfulToken($index);

        if ($name === null || $tokens[ $name ]->isGivenKind([ T_STRING, T_NAME_FULLY_QUALIFIED ]) === false
            || strcasecmp(ltrim($tokens[ $name ]->getContent(), '\\'), 'sprintf') !== 0) {
            return null;
        }

        $previous = $tokens->getPrevMeaningfulToken($name);

        if ($previous !== null && $tokens[ $previous ]->isGivenKind(T_NS_SEPARATOR)) {

            $previous = $tokens->getPrevMeaningfulToken($previous);

            if ($previous !== null && $tokens[ $previous ]->isGivenKind(T_STRING)) {
                return null;
            }

        }

        if ($previous !== null && ($tokens[ $previous ]->isObjectOperator()
                || $tokens[ $previous ]->isGivenKind([ T_DOUBLE_COLON, T_FUNCTION, T_NEW ]))) {
            return null;
        }

        return $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $index);
    }
}

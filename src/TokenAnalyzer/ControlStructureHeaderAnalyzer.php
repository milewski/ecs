<?php

declare(strict_types = 1);

namespace Milewski\ECS\TokenAnalyzer;

use PhpCsFixer\Tokenizer\Tokens;

final class ControlStructureHeaderAnalyzer
{
    public static function findEnd(Tokens $tokens, int $index): ?int
    {
        if ($tokens[ $index ]->isGivenKind([
            T_IF, T_ELSEIF, T_FOR, T_FOREACH, T_WHILE, T_SWITCH, T_MATCH, T_CATCH, T_DECLARE,
        ]) === false) {
            return null;
        }

        $openParenthesis = $tokens->getNextMeaningfulToken($index);

        if ($openParenthesis === null || $tokens[ $openParenthesis ]->equals('(') === false) {
            return null;
        }

        return $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openParenthesis);
    }
}

<?php

declare(strict_types = 1);

namespace Milewski\ECS\TokenAnalyzer;

use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplObjectStorage;

final class BooleanExpressionAnalyzer
{
    /**
     * @var SplObjectStorage<Token, null>
     */
    private SplObjectStorage $expressionTokens;

    public function __construct(Tokens $tokens)
    {
        $this->expressionTokens = new SplObjectStorage();

        foreach ($tokens as $index => $token) {

            if ($this->contains($token) || $this->isOperator($token) === false) {
                continue;
            }

            $start = $this->findBoundary($tokens, $index, -1);
            $end = $this->findBoundary($tokens, $index, 1);

            for ($expressionIndex = $start; $expressionIndex <= $end; $expressionIndex++) {
                $this->expressionTokens[ $tokens[ $expressionIndex ] ] = null;
            }

        }
    }

    public function contains(Token $token): bool
    {
        return isset($this->expressionTokens[ $token ]);
    }

    private function isOperator(Token $token): bool
    {
        return $token->equalsAny([ '!', '<', '>' ]) || $token->isGivenKind([ T_BOOLEAN_AND, T_BOOLEAN_OR, T_LOGICAL_AND, T_LOGICAL_OR, T_LOGICAL_XOR, T_IS_EQUAL, T_IS_IDENTICAL, T_IS_NOT_EQUAL, T_IS_NOT_IDENTICAL, T_IS_SMALLER_OR_EQUAL, T_IS_GREATER_OR_EQUAL, T_SPACESHIP, T_INSTANCEOF ]);
    }

    private function findBoundary(Tokens $tokens, int $operator, int $direction): int
    {
        for ($index = $operator + $direction; $index >= 0 && $index < $tokens->count(); $index += $direction) {

            $token = $tokens[ $index ];
            $block = Tokens::detectBlockType($token);

            if ($block !== null) {

                if ($block[ 'isStart' ] !== ($direction === 1)) {
                    return $index - $direction;
                }

                $index = $direction === 1
                    ? $tokens->findBlockEnd($block[ 'type' ], $index)
                    : $tokens->findBlockStart($block[ 'type' ], $index);

                continue;

            }

            if ($token->equalsAny([ ',', ';', '?', ':' ])
                || ($direction === -1 && $token->equals('='))
                || $token->isGivenKind([ CT::T_NAMED_ARGUMENT_COLON, T_DOUBLE_ARROW, T_RETURN, T_YIELD, T_YIELD_FROM ])) {
                return $index - $direction;
            }

        }

        return $direction === 1 ? $tokens->count() - 1 : 0;
    }
}

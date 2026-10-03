<?php

declare(strict_types = 1);

namespace Milewski\ECS\Fixers;

use Exception;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;
use Throwable;

final class PaddedBlockFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'This fixer improves on the readability of PSR-12 by adding a negative space between blocks (if/else/while etc..) making it clearer and easier to read and understand.',
            [],
        );
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([
            T_IF, T_ELSE, T_FOR, T_FOREACH, T_WHILE, T_DO, T_TRY, T_CATCH, T_FINALLY, T_ELSEIF, T_FUNCTION,
        ]);
    }

    public function getPriority(): int
    {
        // Apply padding after long calls have made previously compact blocks multiline.
        return -80;
    }

    /**
     * @throws Exception
     */
    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = 0, $count = $tokens->count(); $index < $count; $index++) {

            $token = $this->token($tokens, $index);

            if ($token->isGivenKind([
                T_IF, T_ELSE, T_FOR, T_FOREACH, T_WHILE, T_DO, T_TRY, T_CATCH, T_FINALLY, T_ELSEIF,
            ])) {

                $blockEndIndex = $this->fixBlock($tokens, $index);

                if ($blockEndIndex !== null) {
                    $this->ensureBlankLineAfterBlock($tokens, $index, $blockEndIndex);
                }

            } else if ($token->isGivenKind([ T_FUNCTION ])) {

                /**
                 * Anonymous function
                 */
                if ($this->token($tokens, $tokens->getNextMeaningfulToken($index))->equals('(')) {

                    $this->fixBlock($tokens, $index);

                } else {

                    $this->ensureNotPadded($tokens, $index);

                }

            }

        }
    }

    /**
     * @throws Exception
     */
    private function ensureNotPadded(Tokens $tokens, int $start): void
    {
        $boundaries = $this->getBlockBoundaries($tokens, $start);

        if ($boundaries === null) {
            return;
        }

        /**
         * public function name()
         * {
         *   •
         *   // body
         *   •
         * }
         */
        if ($this->countNewLines($tokens, $boundaries[ 0 ] + 1) > 1) {
            $this->removeLineAt($tokens, $boundaries[ 0 ] + 1);
        }

        if ($this->countNewLines($tokens, $boundaries[ 1 ] - 1) > 1) {
            $this->removeLineAt($tokens, $boundaries[ 1 ] - 1);
        }

        $this->removePaddingAfterFinalFunction($tokens, $boundaries[ 1 ]);
    }

    /**
     * @throws Exception
     */
    private function removePaddingAfterFinalFunction(Tokens $tokens, int $blockEndIndex): void
    {
        $next = $tokens->getNextNonWhitespace($blockEndIndex);

        if ($next === null || $this->token($tokens, $next)->equals('}') === false) {
            return;
        }

        $whitespaceIndex = $blockEndIndex + 1;

        if ($this->token($tokens, $whitespaceIndex)->isWhitespace() === false
            || $this->countNewLines($tokens, $whitespaceIndex) <= 1) {
            return;
        }

        $this->removeLineAt($tokens, $whitespaceIndex);
    }

    /**
     * @throws Exception
     */
    private function fixBlock(Tokens $tokens, int $start): ?int
    {
        [ $blockStartIndex, $blockEndIndex ] = $this->getBlockBoundaries($tokens, $start) ?? [ null, null ];

        if ($blockStartIndex === null || $blockEndIndex === null) {
            return null;
        }

        /**
         * if() {
         *   •
         *   return
         *   •
         * }
         */
        //        if ($this->token($tokens, $tokens->getNextMeaningfulToken($blockStartIndex))->isGivenKind(T_RETURN)) {
        //
        //            $this->unwrapNewLines($tokens, $blockStartIndex, $blockEndIndex);
        //
        //            return;
        //
        //        }

        /**
         * if/else/try/catch() {
         *   •
         *   // body
         *   •
         * }
         */
        if ($this->countLinesBetween($tokens, $blockStartIndex + 1, $blockEndIndex - 1) === 2) {

            if ($this->isMultiLevelBlock($tokens, $blockStartIndex, $blockEndIndex) === false) {

                $this->unwrapNewLines($tokens, $blockStartIndex, $blockEndIndex);

                return $blockEndIndex;

            }

        }

        /**
         * if() {
         *   •
         *   // body
         * }
         */
        if ($this->countNewLines($tokens, $blockStartIndex + 1) !== 2) {
            $this->ensureWhitespaceAtIndex($tokens, $blockStartIndex + 1);
        }

        /**
         * if() {
         *   // body
         *   •
         * }
         */
        if ($this->countNewLines($tokens, $blockEndIndex - 1) !== 2) {
            $this->ensureWhitespaceAtIndex($tokens, $blockEndIndex - 1);
        }

        return $blockEndIndex;
    }

    private function ensureBlankLineAfterBlock(Tokens $tokens, int $start, int $blockEndIndex): void
    {
        $nextMeaningfulIndex = $tokens->getNextMeaningfulToken($blockEndIndex);

        if ($nextMeaningfulIndex === null) {
            return;
        }

        $nextMeaningfulToken = $this->token($tokens, $nextMeaningfulIndex);

        if ($nextMeaningfulToken->equals('}')
            || $nextMeaningfulToken->isGivenKind([ T_ELSE, T_ELSEIF, T_CATCH, T_FINALLY ])
            || ($this->token($tokens, $start)->isGivenKind(T_DO) && $nextMeaningfulToken->isGivenKind(T_WHILE))) {
            return;
        }

        $whitespaceIndex = $blockEndIndex + 1;
        $whitespaceToken = $this->token($tokens, $whitespaceIndex);

        if ($whitespaceToken->isWhitespace() === false) {
            return;
        }

        $missingNewLines = 2 - $this->countNewLines($tokens, $whitespaceIndex);

        if ($missingNewLines <= 0) {
            return;
        }

        $tokens[ $whitespaceIndex ] = new Token(
            str_repeat($this->whitespacesConfig->getLineEnding(), $missingNewLines)
            . $whitespaceToken->getContent(),
        );
    }

    /**
     * @throws Exception
     */
    private function unwrapNewLines(Tokens $tokens, int $start, int $end): void
    {
        if ($this->countNewLines($tokens, $start + 1)) {
            $this->removeLineAt($tokens, $start + 1);
        }

        if ($this->countNewLines($tokens, $end - 1)) {
            $this->removeLineAt($tokens, $end - 1);
        }
    }

    private function isMultiLevelBlock(Tokens $tokens, int $start, int $end): bool
    {
        $next = $tokens->getNextMeaningfulToken($end);
        $previous = $tokens->getPrevMeaningfulToken($start);

        if ($this->token($tokens, $previous)->equals(')')) {

            $blockStart = $tokens->findBlockStart(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $previous);
            $previous = $tokens->getPrevMeaningfulToken($blockStart);

            /**
             * else if vs elseif
             */
            if ($this->token($tokens, $previous)->isGivenKind([ T_IF ])) {
                $previous = $tokens->getPrevMeaningfulToken($previous);
            }

        }

        return $this->token($tokens, $next)->isGivenKind([ T_ELSE, T_ELSEIF, T_CATCH, T_DO ])
            || $this->token($tokens, $previous)->isGivenKind([ T_ELSE, T_ELSEIF, T_CATCH, T_DO ]);
    }

    private function getBlockBoundaries(Tokens $tokens, int $start): ?array
    {
        $blockStartIndex = $tokens->getNextTokenOfKind($start, [ '{' ]);

        try {

            $blockEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $blockStartIndex);

        } catch (Throwable) {

            return null;

        }

        /**
         * If body is empty
         * public function name()
         * {
         * }
         */
        if ($blockEndIndex - $blockStartIndex <= 2) {
            return null;
        }

        return [ $blockStartIndex, $blockEndIndex ];
    }

    /**
     * @throws Exception
     */
    private function ensureWhitespaceAtIndex(Tokens $tokens, int $index): void
    {
        if ($this->token($tokens, $index)->isWhitespace() === false) {
            throw new Exception('Currently not an whitespace...');
        }

        $tokens[ $index ] = new Token(
            $this->whitespacesConfig->getLineEnding()
            . $this->token($tokens, $index)->getContent(),
        );
    }

    /**
     * @throws Exception
     */
    private function removeLineAt(Tokens $tokens, int $index): void
    {
        if ($this->token($tokens, $index)->isWhitespace() === false) {
            throw new Exception('Currently not an whitespace...');
        }

        $tokens[ $index ] = new Token(
            $this->whitespacesConfig->getLineEnding()
            . $this->getIndent($tokens, $index),
        );
    }

    private function getIndent(Tokens $tokens, int $index): string
    {
        $content = $this->token($tokens, $index)->getContent();
        $lastNewLine = strrpos($content, "\n");

        if ($lastNewLine === false) {
            return $content;
        }

        return substr($content, $lastNewLine + 1);
    }

    private function countNewLines(Tokens $tokens, int $index): int
    {
        $token = $this->token($tokens, $index);

        if ($token->isWhitespace()) {
            return substr_count($token->getContent(), "\n");
        }

        return 0;
    }

    private function countLinesBetween(Tokens $tokens, int $start, int $end): int
    {
        $lines = 0;

        foreach (range($start, $end) as $index) {

            if ($this->countNewLines($tokens, $index)) {
                $lines++;
            }

        }

        return $lines;
    }

    private function token(Tokens $tokens, int $index): Token
    {
        return $tokens[ $index ];
    }
}

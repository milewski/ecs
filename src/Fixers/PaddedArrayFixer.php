<?php

declare(strict_types = 1);

namespace Milewski\ECS\Fixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class PaddedArrayFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Arrays should always have a space between start and ending brackets.',
            [
                new CodeSample("<?php\n\$sample = [ 1,2,3 ];"),
            ],
        );
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([ CT::T_ARRAY_SQUARE_BRACE_OPEN, T_VARIABLE ]);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = 0, $c = $tokens->count(); $index < $c; $index++) {

            if ($tokens[ $index ]->equals('[')) {
                $this->fixVariable($tokens, $index);
            }

            if ($tokens[ $index ]->isGivenKind([ CT::T_ARRAY_SQUARE_BRACE_OPEN ])) {
                $this->fixArray($tokens, $index);
            }

        }
    }

    private function fixArray(Tokens $tokens, int $openingBracketIndex): void
    {
        /**
         * @var Token $openingBracket
         * @var Token $closingBracket
         */
        $openingBracket = $tokens[ $openingBracketIndex ];
        $closingBracketIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE, $openingBracketIndex);
        $closingBracket = $tokens[ $closingBracketIndex ];
        $nextMeaningFulTokenIndex = $tokens->getNextMeaningfulToken($openingBracketIndex);

        /**
         * [    ] => []
         */
        if ($closingBracketIndex === $nextMeaningFulTokenIndex) {

            $tokens->clearRange($openingBracketIndex + 1, $closingBracketIndex - 1);

            return;

        }

        /**
         * [1,2,3] => [ 1,2,3]
         */
        if ($nextMeaningFulTokenIndex - $openingBracketIndex === 1) {

            $tokens->ensureWhitespaceAtIndex($nextMeaningFulTokenIndex, 0, ' ');

            $closingBracketIndex++;

        }

        /**
         * [1,2,3] => [1,2,3 ]
         */
        $previousMeaningFulTokenIndex = $tokens->getPrevMeaningfulToken($closingBracketIndex);

        if ($closingBracketIndex - $previousMeaningFulTokenIndex === 1) {
            $tokens->ensureWhitespaceAtIndex($previousMeaningFulTokenIndex, 1, ' ');
        }
    }

    private function fixVariable(Tokens $tokens, int $openingBracketIndex): void
    {
        /**
         * $data[]
         */
        if ($tokens[ $openingBracketIndex + 1 ]->equals(']')) {
            return;
        }

        /**
         * $data[0] => $data[ 0]
         */
        $tokens->ensureWhitespaceAtIndex($openingBracketIndex + 1, 0, ' ');

        while (true) {

            $current = $tokens[ $openingBracketIndex++ ] ?? null;

            if ($current === null) {
                break;
            }

            if ($current->equals(']')) {

                /**
                 * $data[0] => $data[0 ]
                 */
                if (!$tokens[ $openingBracketIndex - 2 ]->isWhitespace()) {
                    $tokens->ensureWhitespaceAtIndex($openingBracketIndex - 1, 0, ' ');
                }

                break;

            }

        }
    }
}

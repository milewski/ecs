<?php

declare(strict_types = 1);

namespace Milewski\ECS\Fixers;

use Milewski\ECS\TokenAnalyzer\LineLengthAnalyzer;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\ConfigurableFixerTrait;
use PhpCsFixer\Fixer\IndentationTrait;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class PaddedArrayFixer extends AbstractFixer implements ConfigurableFixerInterface, WhitespacesAwareFixerInterface
{
    use ConfigurableFixerTrait;
    use IndentationTrait;

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            summary: 'Inline arrays have spaces inside their brackets. Long and multiline arrays place each element on its own line.',
            codeSamples: [
                new CodeSample("<?php\n\$sample = [ 1,2,3 ];"),
            ],
        );
    }

    public function getPriority(): int
    {
        // Expand after calls are wrapped, before array indentation and trailing commas.
        return 30;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([ CT::T_ARRAY_SQUARE_BRACE_OPEN, T_VARIABLE ]);
    }

    protected function createConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        return new FixerConfigurationResolver([
            new FixerOptionBuilder('max_line_length', 'Expand arrays on lines longer than this limit.')
                ->setAllowedTypes([ 'int' ])
                ->setAllowedValues([ static fn (int $length): bool => $length > 0 ])
                ->setDefault(140)
                ->getOption(),
        ]);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = 0; $index < $tokens->count(); $index++) {

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
        $closingBracketIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE, $openingBracketIndex);
        $nextMeaningfulTokenIndex = $tokens->getNextMeaningfulToken($openingBracketIndex);

        /**
         * [    ] => []
         */
        if ($closingBracketIndex === $tokens->getNextNonWhitespace($openingBracketIndex)) {

            $tokens->clearRange($openingBracketIndex + 1, $closingBracketIndex - 1);

            return;

        }

        if ($this->shouldExpandArray($tokens, $openingBracketIndex, $closingBracketIndex)) {

            $this->expandArray($tokens, $openingBracketIndex, $closingBracketIndex);

            return;

        }

        /**
         * [1,2,3] => [ 1,2,3]
         */
        if ($nextMeaningfulTokenIndex - $openingBracketIndex === 1) {

            $tokens->ensureWhitespaceAtIndex($nextMeaningfulTokenIndex, 0, ' ');

            $closingBracketIndex++;

        }

        /**
         * [1,2,3] => [1,2,3 ]
         */
        $previousMeaningfulTokenIndex = $tokens->getPrevMeaningfulToken($closingBracketIndex);

        if ($closingBracketIndex - $previousMeaningfulTokenIndex === 1) {
            $tokens->ensureWhitespaceAtIndex($previousMeaningfulTokenIndex, 1, ' ');
        }

        $closingBracketIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE, $openingBracketIndex);

        if ($this->shouldExpandArray($tokens, $openingBracketIndex, $closingBracketIndex)) {
            $this->expandArray($tokens, $openingBracketIndex, $closingBracketIndex);
        }
    }

    private function shouldExpandArray(Tokens $tokens, int $openingBracketIndex, int $closingBracketIndex): bool
    {
        if ($tokens->isPartialCodeMultiline($openingBracketIndex, $closingBracketIndex)) {
            return true;
        }

        return LineLengthAnalyzer::maximumLength($tokens, $openingBracketIndex, $closingBracketIndex)
            > $this->configuration[ 'max_line_length' ];
    }

    private function expandArray(Tokens $tokens, int $openingBracketIndex, int $closingBracketIndex): void
    {
        $indentation = $this->getLineIndentation($tokens, $openingBracketIndex);
        $lineEnding = $this->whitespacesConfig->getLineEnding();
        $elementWhitespace = $lineEnding . $indentation . $this->whitespacesConfig->getIndent();
        $separators = [ $openingBracketIndex ];

        for ($index = $openingBracketIndex + 1; $index < $closingBracketIndex; $index++) {

            $block = Tokens::detectBlockType($tokens[ $index ]);

            if ($block !== null) {

                if ($block[ 'isStart' ]) {

                    $index = $tokens->findBlockEnd($block[ 'type' ], $index);

                    continue;

                }

            }

            if ($tokens[ $index ]->equals(',') === false) {
                continue;
            }

            if ($tokens->getNextMeaningfulToken($index) === $closingBracketIndex) {
                continue;
            }

            $separators[] = $index;

        }

        $tokens->ensureWhitespaceAtIndex($closingBracketIndex - 1, 1, $lineEnding . $indentation);

        for ($index = count($separators) - 1; $index >= 0; $index--) {

            $separator = $separators[ $index ];
            $first = $tokens->getNextNonWhitespace($separator);

            if ($this->hasTrailingLineComment($tokens, $separator, $first, $openingBracketIndex)) {
                $first = $tokens->getNextNonWhitespace($first);
            }

            $tokens->ensureWhitespaceAtIndex($first - 1, 1, $elementWhitespace);

        }
    }

    private function hasTrailingLineComment(Tokens $tokens, int $separator, int $first, int $openingBracketIndex): bool
    {
        if ($separator === $openingBracketIndex) {
            return false;
        }

        if ($tokens[ $first ]->isComment() === false) {
            return false;
        }

        if (\str_starts_with($tokens[ $first ]->getContent(), '/*')) {
            return false;
        }

        return $tokens->isPartialCodeMultiline($separator, $first) === false;
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

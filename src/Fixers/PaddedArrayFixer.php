<?php

declare(strict_types = 1);

namespace Milewski\ECS\Fixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\IndentationTrait;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class PaddedArrayFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    use IndentationTrait;

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            summary: 'Single-line arrays have bracket padding. Multiline arrays place each element and the closing bracket on separate lines.',
            codeSamples: [
                new CodeSample("<?php\n\$sample = [ 1,2,3 ];"),
            ],
        );
    }

    public function getPriority(): int
    {
        return 30;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([ CT::T_ARRAY_SQUARE_BRACE_OPEN, T_VARIABLE ]);
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
        $nextContentIndex = $tokens->getNextNonWhitespace($openingBracketIndex);

        if ($closingBracketIndex === $nextContentIndex) {

            if ($closingBracketIndex > $openingBracketIndex + 1) {
                $tokens->clearRange($openingBracketIndex + 1, $closingBracketIndex - 1);
            }

            return;

        }

        if ($this->isMultiline($tokens, $openingBracketIndex, $closingBracketIndex)) {

            $this->expandArray($tokens, $openingBracketIndex, $closingBracketIndex);

            return;

        }

        if ($tokens[ $closingBracketIndex - 1 ]->isWhitespace() === false) {
            $tokens->ensureWhitespaceAtIndex($closingBracketIndex - 1, 1, ' ');
        }

        if ($tokens[ $openingBracketIndex + 1 ]->isWhitespace() === false) {
            $tokens->ensureWhitespaceAtIndex($openingBracketIndex + 1, 0, ' ');
        }
    }

    private function isMultiline(Tokens $tokens, int $openingBracketIndex, int $closingBracketIndex): bool
    {
        for ($index = $openingBracketIndex + 1; $index < $closingBracketIndex; $index++) {

            if ($tokens[ $index ]->isWhitespace() === false) {

                if ($tokens[ $index ]->isComment() === false) {
                    continue;
                }

            }

            if (str_contains($tokens[ $index ]->getContent(), "\n")) {
                return true;
            }

        }

        return false;
    }

    private function expandArray(Tokens $tokens, int $openingBracketIndex, int $closingBracketIndex): void
    {
        $elementStarts = [ $openingBracketIndex + 1 ];

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

            $nextContentIndex = $tokens->getNextNonWhitespace($index);

            if ($nextContentIndex === $closingBracketIndex) {
                continue;
            }

            if ($tokens[ $nextContentIndex ]->isComment()) {

                if (str_starts_with($tokens[ $nextContentIndex ]->getContent(), '//')) {
                    continue;
                }

                if (str_starts_with($tokens[ $nextContentIndex ]->getContent(), '#')) {
                    continue;
                }

            }

            $elementStarts[] = $index + 1;

        }

        $indentation = $this->getLineIndentation($tokens, $openingBracketIndex);
        $lineEnding = $this->whitespacesConfig->getLineEnding();

        $tokens->ensureWhitespaceAtIndex($closingBracketIndex - 1, 1, $lineEnding . $indentation);

        foreach (array_reverse($elementStarts) as $elementStart) {
            $tokens->ensureWhitespaceAtIndex($elementStart, 0, $lineEnding . $indentation . $this->whitespacesConfig->getIndent());
        }
    }

    private function fixVariable(Tokens $tokens, int $openingBracketIndex): void
    {
        if ($tokens[ $openingBracketIndex + 1 ]->equals(']')) {
            return;
        }

        $tokens->ensureWhitespaceAtIndex($openingBracketIndex + 1, 0, ' ');

        while (true) {

            $current = $tokens[ $openingBracketIndex++ ] ?? null;

            if ($current === null) {
                break;
            }

            if ($current->equals(']')) {

                if (!$tokens[ $openingBracketIndex - 2 ]->isWhitespace()) {
                    $tokens->ensureWhitespaceAtIndex($openingBracketIndex - 1, 0, ' ');
                }

                break;

            }

        }
    }
}

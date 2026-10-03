<?php

declare(strict_types = 1);

namespace Milewski\ECS\Fixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;
use SplFileInfo;

final class TraitUseSpacingFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Trait imports must be contiguous and separated from the following class member by one blank line.',
            [],
        );
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(CT::T_USE_TRAIT);
    }

    public function getPriority(): int
    {
        return -1;
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        $elementsByClass = [];

        foreach ((new TokensAnalyzer($tokens))->getClassyElements() as $index => $element) {
            $elementsByClass[ $element[ 'classIndex' ] ][ $index ] = $element[ 'type' ];
        }

        foreach ($elementsByClass as $elements) {

            ksort($elements);

            $elementIndexes = array_keys($elements);
            $elementTypes = array_values($elements);

            for ($position = count($elementIndexes) - 1; $position >= 0; $position--) {

                if ($elementTypes[ $position ] !== 'trait_import') {
                    continue;
                }

                $nextElementType = $elementTypes[ $position + 1 ] ?? null;
                $requiredLineBreaks = $nextElementType === null || $nextElementType === 'trait_import' ? 1 : 2;
                $traitEndIndex = $this->findTraitEndIndex($tokens, $elementIndexes[ $position ]);

                $this->normalizeLineBreaksAfter($tokens, $traitEndIndex, $requiredLineBreaks);

            }

        }
    }

    private function findTraitEndIndex(Tokens $tokens, int $traitUseIndex): int
    {
        $terminatorIndex = $tokens->getNextTokenOfKind($traitUseIndex, [ ';', '{' ]);

        if ($tokens[ $terminatorIndex ]->equals('{')) {
            return $tokens->findBlockEnd(Tokens::BLOCK_TYPE_BRACE, $terminatorIndex);
        }

        return $terminatorIndex;
    }

    private function normalizeLineBreaksAfter(Tokens $tokens, int $traitEndIndex, int $requiredLineBreaks): void
    {
        $spacingBoundaryIndex = $this->findSpacingBoundaryIndex($tokens, $traitEndIndex);
        $whitespaceIndex = $spacingBoundaryIndex + 1;
        $lineEnding = $this->whitespacesConfig->getLineEnding();

        if (!$tokens[ $whitespaceIndex ]->isWhitespace()) {

            $tokens->insertAt(
                $whitespaceIndex,
                new Token([ T_WHITESPACE, str_repeat($lineEnding, $requiredLineBreaks) ]),
            );

            return;

        }

        $content = $tokens[ $whitespaceIndex ]->getContent();
        $lastLineBreakPosition = strrpos($content, "\n");
        $indentation = $lastLineBreakPosition === false ? $content : substr($content, $lastLineBreakPosition + 1);

        $tokens[ $whitespaceIndex ] = new Token([
            T_WHITESPACE,
            sprintf('%s%s', str_repeat($lineEnding, $requiredLineBreaks), $indentation),
        ]);
    }

    private function findSpacingBoundaryIndex(Tokens $tokens, int $traitEndIndex): int
    {
        $nextIndex = $traitEndIndex + 1;

        if ($tokens[ $nextIndex ]->isComment()) {
            return $nextIndex;
        }

        if (!$tokens[ $nextIndex ]->isWhitespace() || str_contains($tokens[ $nextIndex ]->getContent(), "\n")) {
            return $traitEndIndex;
        }

        $commentIndex = $tokens->getNextNonWhitespace($traitEndIndex);

        if ($commentIndex !== null && $tokens[ $commentIndex ]->isComment()) {
            return $commentIndex;
        }

        return $traitEndIndex;
    }
}

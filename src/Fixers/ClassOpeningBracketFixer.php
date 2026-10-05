<?php

declare(strict_types = 1);

namespace Milewski\ECS\Fixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\IndentationTrait;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class ClassOpeningBracketFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    use IndentationTrait;

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            summary: 'Class, interface, and trait opening braces must start on the next line without extra blank lines inside the body.',
            codeSamples: [],
        );
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([ T_CLASS, T_INTERFACE, T_TRAIT ]);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index >= 0; $index--) {

            if ($tokens[ $index ]->isGivenKind([ T_CLASS, T_INTERFACE, T_TRAIT ]) === false) {
                continue;
            }

            $openBracketsIndex = $this->findOpeningBrace($tokens, $index);

            if ($openBracketsIndex === null) {
                continue;
            }

            $closeBracketsIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $openBracketsIndex);

            $this->removeExtraBlankLines($tokens, $closeBracketsIndex - 1);
            $this->removeExtraBlankLines($tokens, $openBracketsIndex + 1);

            $indentation = $this->getLineIndentation($tokens, $index);

            $tokens->ensureWhitespaceAtIndex(
                index: $openBracketsIndex - 1,
                indexOffset: 1,
                whitespace: $this->whitespacesConfig->getLineEnding() . $indentation,
            );

        }
    }

    private function findOpeningBrace(Tokens $tokens, int $classIndex): ?int
    {
        for ($index = $classIndex + 1; $index < $tokens->count(); $index++) {

            if ($tokens[ $index ]->equals('{')) {
                return $index;
            }

            $block = Tokens::detectBlockType($tokens[ $index ]);

            if ($block !== null && $block[ 'isStart' ]) {
                $index = $tokens->findBlockEnd($block[ 'type' ], $index);
            }

        }

        return null;
    }

    private function removeExtraBlankLines(Tokens $tokens, int $index): void
    {
        if ($tokens[ $index ]->isWhitespace() === false) {
            return;
        }

        $lines = preg_split('/\R/', $tokens[ $index ]->getContent());

        if (count($lines) > 2) {
            $tokens[ $index ] = new Token([ T_WHITESPACE, $this->whitespacesConfig->getLineEnding() . end($lines) ]);
        }
    }
}

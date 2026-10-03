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

final class PaddedDocblockFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    use IndentationTrait;

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            summary: 'Docblocks following completed statements or blocks must have a blank line before them.',
            codeSamples: [],
        );
    }

    public function getPriority(): int
    {
        return -105;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_DOC_COMMENT);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index >= 0; $index--) {

            if ($tokens[ $index ]->isGivenKind(T_DOC_COMMENT) === false) {
                continue;
            }

            $previous = $tokens->getPrevMeaningfulToken($index);

            if ($previous === null || $tokens[ $previous ]->equalsAny([ ';', '}' ]) === false) {
                continue;
            }

            if ($tokens->getPrevNonWhitespace($index) !== $previous) {
                continue;
            }

            $this->ensureBlankLineBefore($tokens, $index);

        }
    }

    private function ensureBlankLineBefore(Tokens $tokens, int $docblock): void
    {
        $lineEnding = $this->whitespacesConfig->getLineEnding();
        $whitespace = $docblock - 1;

        if ($tokens[ $whitespace ]->isWhitespace() === false) {

            $tokens->insertAt(
                index: $docblock,
                items: new Token([
                    T_WHITESPACE,
                    sprintf(
                        '%1$s%1$s%2$s',
                        $lineEnding,
                        $this->getLineIndentation($tokens, $docblock),
                    ),
                ]),
            );

            return;

        }

        $content = $tokens[ $whitespace ]->getContent();

        if (substr_count($content, "\n") >= 2) {
            return;
        }

        if (str_contains($content, "\n")) {

            $tokens[ $whitespace ] = new Token([
                T_WHITESPACE,
                sprintf('%s%s', $lineEnding, $content),
            ]);

            return;

        }

        $tokens[ $whitespace ] = new Token([
            T_WHITESPACE,
            sprintf(
                '%1$s%1$s%2$s',
                $lineEnding,
                $this->getLineIndentation($tokens, $docblock),
            ),
        ]);
    }
}

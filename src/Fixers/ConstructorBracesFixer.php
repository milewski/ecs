<?php

declare(strict_types = 1);

namespace Milewski\ECS\Fixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\IndentationTrait;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class ConstructorBracesFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    use IndentationTrait;

    private const string CONSTRUCTOR_NAME = '__construct';

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Constructor braces must each be placed on a separate line.',
            [],
        );
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_FUNCTION);
    }

    public function getPriority(): int
    {
        return -10;
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $index => $token) {

            if ($token->isGivenKind(T_FUNCTION) === false) {
                continue;
            }

            $nameIndex = $tokens->getNextMeaningfulToken($index);

            if ($tokens[ $nameIndex ]->equals('&')) {
                $nameIndex = $tokens->getNextMeaningfulToken($nameIndex);
            }

            if ($tokens[ $nameIndex ]->equals([ T_STRING, self::CONSTRUCTOR_NAME ], false) === false) {
                continue;
            }

            $openBraceIndex = $tokens->getNextTokenOfKind($nameIndex, [ '{', ';' ]);

            if ($openBraceIndex === null || $tokens[ $openBraceIndex ]->equals('{') === false) {
                continue;
            }

            $closeBraceIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $openBraceIndex);
            $indentation = $this->getLineIndentation($tokens, $index);
            $lineEnding = $this->whitespacesConfig->getLineEnding();

            if ($this->isEmptyBody($tokens, $openBraceIndex, $closeBraceIndex)) {

                $tokens->ensureWhitespaceAtIndex($openBraceIndex + 1, 0, $lineEnding . $indentation);

            } else {

                $tokens->ensureWhitespaceAtIndex(
                    $closeBraceIndex - 1,
                    1,
                    $lineEnding . $indentation,
                );

                $tokens->ensureWhitespaceAtIndex(
                    $openBraceIndex + 1,
                    0,
                    $lineEnding . $indentation . $this->whitespacesConfig->getIndent(),
                );

            }

            $tokens->ensureWhitespaceAtIndex($openBraceIndex - 1, 1, $lineEnding . $indentation);

        }
    }

    private function isEmptyBody(Tokens $tokens, int $openBraceIndex, int $closeBraceIndex): bool
    {
        for ($index = $openBraceIndex + 1; $index < $closeBraceIndex; $index++) {

            if ($tokens[ $index ]->isWhitespace() === false && $tokens->isEmptyAt($index) === false) {
                return false;
            }

        }

        return true;
    }
}

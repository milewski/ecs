<?php

declare(strict_types = 1);

namespace Milewski\ECS\Fixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class ClassOpeningBracketFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
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
        foreach ($tokens as $index => $token) {

            if ($token->isGivenKind([ T_CLASS, T_INTERFACE, T_TRAIT ])) {

                $openBracketsIndex = $tokens->getNextTokenOfKind($index, [ '{' ]);
                $closeBracketsIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $openBracketsIndex);
                $beforeOpenBracketToken = $tokens[ $openBracketsIndex + 1 ];
                $beforeCloseBracketToken = $tokens[ $closeBracketsIndex - 1 ];
                $openWhitespace = preg_replace('~\R+~', $this->whitespacesConfig->getLineEnding(), $beforeOpenBracketToken->getContent());
                $closeWhitespace = preg_replace('~\R+~', $this->whitespacesConfig->getLineEnding(), $beforeCloseBracketToken->getContent());

                if ($beforeOpenBracketToken->isWhitespace() && substr_count($beforeOpenBracketToken->getContent(), PHP_EOL) > 1) {
                    $tokens[ $openBracketsIndex + 1 ] = new Token([ T_WHITESPACE, $openWhitespace ]);
                }

                if ($beforeCloseBracketToken->isWhitespace() && substr_count($beforeCloseBracketToken->getContent(), PHP_EOL) > 1) {
                    $tokens[ $closeBracketsIndex - 1 ] = new Token([ T_WHITESPACE, $closeWhitespace ]);
                }

                $token = $tokens[ $openBracketsIndex - 1 ];

                if (str_contains($token->getContent(), PHP_EOL) === false) {
                    $tokens[ $openBracketsIndex - 1 ] = new Token([ T_WHITESPACE, PHP_EOL ]);
                }

            }

        }
    }
}

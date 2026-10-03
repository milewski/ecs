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

final class PaddedMultilineStatementFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    use IndentationTrait;

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Multiline statements must be followed by a blank line unless they end a block.',
            [],
        );
    }

    public function getPriority(): int
    {
        return -100;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(';');
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        $statements = $this->findMultilineStatements($tokens);

        for ($index = count($statements) - 1; $index >= 0; $index--) {

            $statement = $statements[ $index ];

            $this->ensureBlankLineAfter(
                $tokens,
                $statement[ 'start' ],
                $statement[ 'end' ],
            );

        }
    }

    /**
     * @return list<array{start: int, end: int}>
     */
    private function findMultilineStatements(Tokens $tokens): array
    {
        $statements = [];
        $parenthesisDepth = 0;
        $squareBracketDepth = 0;

        foreach ($tokens as $index => $token) {

            if ($token->equals('(')) {

                $parenthesisDepth++;

                continue;

            }

            if ($token->equals(')')) {

                $parenthesisDepth--;

                continue;

            }

            if ($token->equals('[')) {

                $squareBracketDepth++;

                continue;

            }

            if ($token->equals(']')) {

                $squareBracketDepth--;

                continue;

            }

            if ($parenthesisDepth > 0 || $squareBracketDepth > 0 || $token->equals(';') === false) {
                continue;
            }

            $start = $this->findStatementStart($tokens, $index);

            if ($start === null || $tokens->isPartialCodeMultiline($start, $index) === false) {
                continue;
            }

            $statements[] = [
                'start' => $start,
                'end' => $this->findEndIncludingTrailingComment($tokens, $index),
            ];

        }

        return $statements;
    }

    private function findStatementStart(Tokens $tokens, int $end): ?int
    {
        $cursor = $end;

        while (($previous = $tokens->getPrevMeaningfulToken($cursor)) !== null) {

            $token = $tokens[ $previous ];

            if ($token->equalsAny([ ';', '{' ]) || $token->isGivenKind(T_OPEN_TAG)) {
                return $tokens->getNextMeaningfulToken($previous);
            }

            $block = Tokens::detectBlockType($token);

            if ($block === null || $block[ 'isStart' ]) {

                $cursor = $previous;

                continue;

            }

            if ($token->equals('}')) {

                $next = $tokens->getNextMeaningfulToken($previous);

                if ($next === null || $this->continuesExpression($tokens[ $next ]) === false) {
                    return $next;
                }

            }

            $cursor = $tokens->findBlockStart($block[ 'type' ], $previous);

        }

        return null;
    }

    private function continuesExpression(Token $token): bool
    {
        return $token->equalsAny([ ';', ',', '(', ')', ']' ])
            || $token->isGivenKind([ T_DOUBLE_COLON, ...Token::getObjectOperatorKinds() ]);
    }

    private function findEndIncludingTrailingComment(Tokens $tokens, int $end): int
    {
        $next = $tokens->getNextNonWhitespace($end);

        if ($next === null || $tokens[ $next ]->isComment() === false) {
            return $end;
        }

        $whitespace = $end + 1;

        if ($tokens[ $whitespace ]->isWhitespace() === false
            || str_contains($tokens[ $whitespace ]->getContent(), "\n")) {
            return $end;
        }

        return $next;
    }

    private function ensureBlankLineAfter(Tokens $tokens, int $start, int $end): void
    {
        $next = $tokens->getNextNonWhitespace($end);

        if ($next === null
            || $tokens[ $next ]->equals('}')
            || $tokens[ $next ]->isGivenKind(T_CLOSE_TAG)) {
            return;
        }

        $lineEnding = $this->whitespacesConfig->getLineEnding();
        $indentation = $this->getLineIndentation($tokens, $start);
        $whitespace = $end + 1;

        if ($tokens[ $whitespace ]->isWhitespace() === false) {

            $tokens->insertAt(
                $whitespace,
                new Token([ T_WHITESPACE, sprintf('%1$s%1$s%2$s', $lineEnding, $indentation) ]),
            );

            return;

        }

        $content = $tokens[ $whitespace ]->getContent();
        $lineBreaks = substr_count($content, "\n");

        if ($lineBreaks >= 2) {
            return;
        }

        if ($lineBreaks === 1) {

            $tokens[ $whitespace ] = new Token([ T_WHITESPACE, sprintf('%s%s', $lineEnding, $content) ]);

            return;

        }

        $tokens[ $whitespace ] = new Token([
            T_WHITESPACE,
            sprintf('%1$s%1$s%2$s', $lineEnding, $indentation),
        ]);
    }
}

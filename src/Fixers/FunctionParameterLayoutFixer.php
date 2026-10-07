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
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class FunctionParameterLayoutFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    use IndentationTrait;

    private const string CONSTRUCTOR_NAME = '__construct';

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            summary: 'Named function body braces use separate lines. Simple named function parameters always use one line; constructors use multiline parameters unless they have exactly one parameter and a non-empty body.',
            codeSamples: [
                new CodeSample("<?php\n\nfinal class Example\n{\n    public function __construct(public readonly string \$name)\n    {\n    }\n\n    public static function create(\n        string \$name,\n    ): self\n    {\n    }\n}\n"),
            ],
        );
    }

    public function getPriority(): int
    {
        return 940;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_FUNCTION);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index >= 0; $index--) {

            if ($tokens[ $index ]->isGivenKind(T_FUNCTION) === false) {
                continue;
            }

            $nameIndex = $this->findFunctionName($tokens, $index);

            if ($nameIndex === null) {
                continue;
            }

            $openParenthesis = $tokens->getNextTokenOfKind($nameIndex, [ '(' ]);

            if ($openParenthesis === null) {
                continue;
            }

            $closeParenthesis = $tokens->findBlockEnd(
                Tokens::BLOCK_TYPE_PARENTHESIS_BRACE,
                $openParenthesis,
            );

            $whitespace = $this->inspectSimpleParameters($tokens, $openParenthesis, $closeParenthesis);
            $isConstructor = $tokens[ $nameIndex ]->equals([ T_STRING, self::CONSTRUCTOR_NAME ], false);
            $shouldCompact = $whitespace !== null
                && ($isConstructor === false
                    || ($this->countParameters($tokens, $openParenthesis, $closeParenthesis) === 1
                        && $this->hasNonEmptyBody($tokens, $closeParenthesis)));

            if ($shouldCompact) {

                $this->compactFunctionParameters($tokens, $openParenthesis, $closeParenthesis, $whitespace);

            } elseif ($isConstructor) {

                $this->expandConstructorParameters(
                    $tokens,
                    $index,
                    $openParenthesis,
                    $closeParenthesis,
                );

            }

            $this->placeBodyBracesOnOwnLines($tokens, $index, $closeParenthesis);

        }
    }

    private function findFunctionName(Tokens $tokens, int $functionIndex): ?int
    {
        $nameIndex = $tokens->getNextMeaningfulToken($functionIndex);

        if ($nameIndex === null) {
            return null;
        }

        if ($tokens[ $nameIndex ]->isGivenKind(CT::T_RETURN_REF)) {
            $nameIndex = $tokens->getNextMeaningfulToken($nameIndex);
        }

        if ($nameIndex === null || $tokens[ $nameIndex ]->isGivenKind(T_STRING) === false) {
            return null;
        }

        return $nameIndex;
    }

    private function hasNonEmptyBody(Tokens $tokens, int $closeParenthesis): bool
    {
        $openBrace = $tokens->getNextTokenOfKind($closeParenthesis, [ '{', ';' ]);

        if ($openBrace === null || $tokens[ $openBrace ]->equals('{') === false) {
            return false;
        }

        $closeBrace = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $openBrace);
        $firstBodyToken = $tokens->getNextMeaningfulToken($openBrace);

        return $firstBodyToken !== null && $firstBodyToken !== $closeBrace;
    }

    private function countParameters(Tokens $tokens, int $openParenthesis, int $closeParenthesis): int
    {
        $firstParameter = $tokens->getNextMeaningfulToken($openParenthesis);

        if ($firstParameter === null || $firstParameter === $closeParenthesis) {
            return 0;
        }

        $commas = $this->findTopLevelCommas($tokens, $openParenthesis, $closeParenthesis);
        $lastParameterToken = $tokens->getPrevMeaningfulToken($closeParenthesis);
        $hasTrailingComma = $lastParameterToken !== null && $tokens[ $lastParameterToken ]->equals(',');

        return count($commas) + ($hasTrailingComma ? 0 : 1);
    }

    private function placeBodyBracesOnOwnLines(Tokens $tokens, int $functionIndex, int $closeParenthesis): void
    {
        $openBrace = $tokens->getNextTokenOfKind($closeParenthesis, [ '{', ';' ]);

        if ($openBrace === null || $tokens[ $openBrace ]->equals('{') === false) {
            return;
        }

        $closeBrace = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $openBrace);
        $indentation = $this->getLineIndentation($tokens, $functionIndex);
        $lineEnding = $this->whitespacesConfig->getLineEnding();

        if ($tokens->getNextNonWhitespace($openBrace) === $closeBrace) {

            $tokens->ensureWhitespaceAtIndex($openBrace + 1, 0, $lineEnding . $indentation);

        } else {

            $this->ensureLineBreakAtIndex($tokens, $closeBrace - 1, 1, $lineEnding . $indentation);
            $this->ensureLineBreakAtIndex(
                tokens: $tokens,
                index: $openBrace + 1,
                offset: 0,
                whitespace: $lineEnding . $indentation . $this->whitespacesConfig->getIndent(),
            );

        }

        $tokens->ensureWhitespaceAtIndex($openBrace - 1, 1, $lineEnding . $indentation);
    }

    private function ensureLineBreakAtIndex(Tokens $tokens, int $index, int $offset, string $whitespace): void
    {
        if ($tokens[ $index ]->isWhitespace() && strpbrk($tokens[ $index ]->getContent(), "\r\n") !== false) {
            return;
        }

        $tokens->ensureWhitespaceAtIndex($index, $offset, $whitespace);
    }

    private function expandConstructorParameters(Tokens $tokens, int $functionIndex, int $openParenthesis, int $closeParenthesis): void
    {
        $firstParameter = $tokens->getNextMeaningfulToken($openParenthesis);

        if ($firstParameter === null || $firstParameter === $closeParenthesis) {
            return;
        }

        $separatorCommas = $this->findTopLevelCommas($tokens, $openParenthesis, $closeParenthesis);
        $trailingComma = $tokens->getPrevMeaningfulToken($closeParenthesis);

        if ($trailingComma === null || $tokens[ $trailingComma ]->equals(',') === false) {

            $tokens->insertAt($closeParenthesis, new Token(','));

            $trailingComma = $closeParenthesis;

        }

        $indentation = $this->getLineIndentation($tokens, $functionIndex);
        $lineEnding = $this->whitespacesConfig->getLineEnding();
        $parameterIndentation = sprintf('%s%s', $indentation, $this->whitespacesConfig->getIndent());

        $this->setWhitespaceAfter(
            $tokens,
            $trailingComma,
            sprintf('%s%s', $lineEnding, $indentation),
        );

        for ($index = count($separatorCommas) - 1; $index >= 0; $index--) {

            $comma = $separatorCommas[ $index ];

            if ($comma === $trailingComma) {
                continue;
            }

            $this->setWhitespaceAfter(
                $tokens,
                $comma,
                sprintf('%s%s', $lineEnding, $parameterIndentation),
            );

        }

        $this->setWhitespaceAfter(
            $tokens,
            $openParenthesis,
            sprintf('%s%s', $lineEnding, $parameterIndentation),
        );
    }

    private function setWhitespaceAfter(Tokens $tokens, int $index, string $content): void
    {
        $whitespaceIndex = $index + 1;

        if ($tokens[ $whitespaceIndex ]->isWhitespace()) {

            $tokens[ $whitespaceIndex ] = new Token([ T_WHITESPACE, $content ]);

            return;

        }

        $tokens->insertAt($whitespaceIndex, new Token([ T_WHITESPACE, $content ]));
    }

    /**
     * @param list<int> $whitespace
     */
    private function compactFunctionParameters(Tokens $tokens, int $openParenthesis, int $closeParenthesis, array $whitespace): void
    {
        for ($index = count($whitespace) - 1; $index >= 0; $index--) {

            $whitespaceIndex = $whitespace[ $index ];
            $previous = $tokens->getPrevMeaningfulToken($whitespaceIndex);
            $next = $tokens->getNextMeaningfulToken($whitespaceIndex);

            if ($previous === null
                || $next === null
                || $previous === $openParenthesis
                || $next === $closeParenthesis
                || $tokens[ $next ]->equals(',')) {

                $tokens->clearAt($whitespaceIndex);

                continue;

            }

            $tokens[ $whitespaceIndex ] = new Token([ T_WHITESPACE, ' ' ]);

        }

        $trailingComma = $tokens->getPrevMeaningfulToken($closeParenthesis);

        if ($trailingComma !== null && $tokens[ $trailingComma ]->equals(',')) {
            $tokens->clearAt($trailingComma);
        }
    }

    /**
     * @return list<int>|null
     */
    private function inspectSimpleParameters(Tokens $tokens, int $openParenthesis, int $closeParenthesis): ?array
    {
        $whitespace = [];

        for ($index = $openParenthesis + 1; $index < $closeParenthesis; $index++) {

            $token = $tokens[ $index ];

            if ($token->isComment() || ($token->isWhitespace() === false && str_contains($token->getContent(), "\n"))) {
                return null;
            }

            $block = Tokens::detectBlockType($token);

            if ($block !== null && $block[ 'isStart' ]) {

                $blockEnd = $tokens->findBlockEnd($block[ 'type' ], $index);

                if ($block[ 'type' ] === Tokens::BLOCK_TYPE_ATTRIBUTE
                    || $this->containsLineBreak($tokens, $index, $blockEnd)) {
                    return null;
                }

                $index = $blockEnd;

                continue;

            }

            if ($token->isWhitespace() && str_contains($token->getContent(), "\n")) {
                $whitespace[] = $index;
            }

        }

        return $whitespace;
    }

    /**
     * @return list<int>
     */
    private function findTopLevelCommas(Tokens $tokens, int $openParenthesis, int $closeParenthesis): array
    {
        $commas = [];

        for ($index = $openParenthesis + 1; $index < $closeParenthesis; $index++) {

            $token = $tokens[ $index ];
            $block = Tokens::detectBlockType($token);

            if ($block !== null && $block[ 'isStart' ]) {

                $index = $tokens->findBlockEnd($block[ 'type' ], $index);

                continue;

            }

            if ($token->equals(',')) {
                $commas[] = $index;
            }

        }

        return $commas;
    }

    private function containsLineBreak(Tokens $tokens, int $start, int $end): bool
    {
        for ($index = $start; $index <= $end; $index++) {

            if (str_contains($tokens[ $index ]->getContent(), "\n")) {
                return true;
            }

        }

        return false;
    }
}

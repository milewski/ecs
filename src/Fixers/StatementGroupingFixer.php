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

final class StatementGroupingFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    use IndentationTrait;

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            summary: 'Adds blank lines between adjacent expression statements with different receivers or call types without removing existing separation.',
            codeSamples: [],
        );
    }

    public function getPriority(): int
    {
        return -110;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(';');
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        $statements = $this->findGroupableStatements($tokens);
        $previousByScope = [];
        $statementPairs = [];

        foreach ($statements as $statement) {

            $scope = $statement[ 'scope' ];
            $previous = $previousByScope[ $scope ] ?? null;

            if ($previous !== null) {
                $statementPairs[] = [ 'previous' => $previous, 'current' => $statement ];
            }

            $previousByScope[ $scope ] = $statement;

        }

        for ($index = count($statementPairs) - 1; $index >= 0; $index--) {

            $previous = $statementPairs[ $index ][ 'previous' ];
            $current = $statementPairs[ $index ][ 'current' ];

            if ($this->containsOnlyWhitespace($tokens, $previous[ 'end' ], $current[ 'start' ]) === false) {
                continue;
            }

            if ($this->belongToSameGroup($previous, $current) && $previous[ 'multiline' ] === false) {
                continue;
            }

            $this->ensureBlankLineBetween(
                tokens: $tokens,
                previousEnd: $previous[ 'end' ],
                currentStart: $current[ 'start' ],
            );

        }
    }

    /**
     * @return list<array{start: int, end: int, kind: string, receiver: ?string, multiline: bool, scope: int}>
     */
    private function findGroupableStatements(Tokens $tokens): array
    {
        $statements = [];
        $nestedExpressionDepth = 0;
        $enclosingExpressionDepths = [];
        $scopeStack = [ 0 ];
        $nextScope = 1;

        foreach ($tokens as $index => $token) {

            $block = Tokens::detectBlockType($token);

            if ($block !== null) {

                if ($this->isExpressionBlock($block[ 'type' ])) {

                    $nestedExpressionDepth += $block[ 'isStart' ] ? 1 : -1;

                    continue;

                }

                if ($block[ 'isStart' ]) {

                    $enclosingExpressionDepths[] = $nestedExpressionDepth;
                    $nestedExpressionDepth = 0;
                    $scopeStack[] = $nextScope++;

                } else {

                    $nestedExpressionDepth = array_pop($enclosingExpressionDepths) ?? 0;

                    array_pop($scopeStack);

                }

                continue;

            }

            if ($nestedExpressionDepth > 0 || $token->equals(';') === false) {
                continue;
            }

            $start = $this->findStatementStart($tokens, $index);

            if ($start === null) {
                continue;
            }

            $group = $this->findGroup($tokens, $start, $index);

            if ($group === null) {
                continue;
            }

            $statements[] = [
                'start' => $start,
                'end' => $index,
                'kind' => $group[ 'kind' ],
                'receiver' => $group[ 'receiver' ],
                'multiline' => $tokens->isPartialCodeMultiline($start, $index),
                'scope' => $scopeStack[ count($scopeStack) - 1 ],
            ];

        }

        return $statements;
    }

    private function isExpressionBlock(int $blockType): bool
    {
        return !in_array($blockType, [
            Tokens::BLOCK_TYPE_BRACE,
            Tokens::BLOCK_TYPE_PROPERTY_HOOK,
        ], true);
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

    /**
     * @return array{kind: string, receiver: ?string}|null
     */
    private function findGroup(Tokens $tokens, int $start, int $end): ?array
    {
        $propertyGroup = $this->findPropertyGroup($tokens, $start, $end);

        if ($propertyGroup !== null) {

            return [
                'kind' => $propertyGroup,
                'receiver' => null,
            ];

        }

        $token = $tokens[ $start ];
        $receiver = $token->isGivenKind(T_VARIABLE) ? $token->getContent() : null;

        if ($this->isAssignment($tokens, $start, $end)) {

            $kind = $token->getContent() === '[' || $token->isGivenKind(T_LIST)
                ? 'assignment:destructuring'
                : 'assignment';

            return [
                'kind' => $kind,
                'receiver' => $receiver,
            ];

        }

        if ($receiver !== null) {

            return [
                'kind' => 'variable-receiver',
                'receiver' => $receiver,
            ];

        }

        $next = $tokens->getNextMeaningfulToken($start);

        if ($next === null) {
            return null;
        }

        if ($token->isGivenKind(T_NEW)) {

            $className = $tokens[ $next ]->isGivenKind([
                T_STRING,
                T_NAME_QUALIFIED,
                T_NAME_FULLY_QUALIFIED,
                T_NAME_RELATIVE,
                T_STATIC,
            ]) ? $tokens[ $next ]->getContent() : null;

            return [
                'kind' => 'new-object-call',
                'receiver' => $className,
            ];

        }

        if ($tokens[ $next ]->isGivenKind(T_DOUBLE_COLON)) {

            return [
                'kind' => 'static-call',
                'receiver' => null,
            ];

        }

        if ($tokens[ $next ]->equals('(')) {

            return [
                'kind' => 'function-call',
                'receiver' => null,
            ];

        }

        return null;
    }

    private function findPropertyGroup(Tokens $tokens, int $start, int $end): ?string
    {
        $hasVisibility = false;
        $isStatic = false;
        $hasUnionType = false;
        $hasIntersectionType = false;
        $typeParts = [];
        $scopeDepth = 0;

        for ($index = $start; $index < $end; $index++) {

            $token = $tokens[ $index ];
            $block = Tokens::detectBlockType($token);

            if ($block !== null && $this->isExpressionBlock($block[ 'type' ]) === false) {

                $scopeDepth += $block[ 'isStart' ] ? 1 : -1;

                continue;

            }

            if ($scopeDepth > 0) {
                continue;
            }

            if ($token->isGivenKind([ T_FUNCTION, T_CONST ])) {
                return null;
            }

            if ($token->isGivenKind([ T_PUBLIC, T_PROTECTED, T_PRIVATE, T_VAR ])) {

                $hasVisibility = true;

                continue;

            }

            if ($token->isGivenKind(T_STATIC)) {

                $isStatic = true;

                continue;

            }

            if ($token->isGivenKind([ T_READONLY, T_FINAL ]) || $token->isWhitespace() || $token->isComment()) {
                continue;
            }

            if ($block !== null && $block[ 'isStart' ] && $block[ 'type' ] === Tokens::BLOCK_TYPE_ATTRIBUTE) {

                $index = $tokens->findBlockEnd($block[ 'type' ], $index);

                continue;

            }

            if ($token->isGivenKind(T_VARIABLE)) {

                if ($hasVisibility === false) {
                    return null;
                }

                if ($isStatic) {
                    return 'property:static';
                }

                if ($hasUnionType) {
                    return 'property:union';
                }

                if ($hasIntersectionType) {
                    return 'property:intersection';
                }

                $type = strtolower(implode('', $typeParts));

                return $type === ''
                    ? 'property:untyped'
                    : sprintf('property:type:%s', $type);

            }

            if ($token->getContent() === '|') {

                $hasUnionType = true;

                continue;

            }

            if ($token->getContent() === '&') {

                $hasIntersectionType = true;

                continue;

            }

            $typeParts[] = $token->getContent();

        }

        return null;
    }

    private function isAssignment(Tokens $tokens, int $start, int $end): bool
    {
        $blockDepth = 0;

        for ($index = $start; $index < $end; $index++) {

            $token = $tokens[ $index ];
            $block = Tokens::detectBlockType($token);

            if ($block !== null) {

                $blockDepth += $block[ 'isStart' ] ? 1 : -1;

                continue;

            }

            if ($blockDepth === 0 && $token->equals('=')) {
                return true;
            }

            if ($blockDepth === 0 && $token->isGivenKind([
                T_AND_EQUAL,
                T_COALESCE_EQUAL,
                T_CONCAT_EQUAL,
                T_DIV_EQUAL,
                T_MINUS_EQUAL,
                T_MOD_EQUAL,
                T_MUL_EQUAL,
                T_OR_EQUAL,
                T_PLUS_EQUAL,
                T_POW_EQUAL,
                T_SL_EQUAL,
                T_SR_EQUAL,
                T_XOR_EQUAL,
            ])) {
                return true;
            }

        }

        return false;
    }

    /**
     * @param array{kind: string, receiver: ?string} $previous
     * @param array{kind: string, receiver: ?string} $current
     */
    private function belongToSameGroup(array $previous, array $current): bool
    {
        if ($previous[ 'receiver' ] !== null
            && $previous[ 'receiver' ] === $current[ 'receiver' ]) {
            return true;
        }

        if ($previous[ 'kind' ] !== $current[ 'kind' ]) {
            return false;
        }

        return in_array($previous[ 'kind' ], [
            'assignment',
            'assignment:destructuring',
            'static-call',
            'function-call',
        ], true) || str_starts_with($previous[ 'kind' ], 'property:');
    }

    private function containsOnlyWhitespace(Tokens $tokens, int $previousEnd, int $currentStart): bool
    {
        for ($index = $previousEnd + 1; $index < $currentStart; $index++) {

            if ($tokens[ $index ]->isWhitespace() === false && $tokens->isEmptyAt($index) === false) {
                return false;
            }

        }

        return true;
    }

    private function ensureBlankLineBetween(Tokens $tokens, int $previousEnd, int $currentStart): void
    {
        $lineBreaks = 0;
        $whitespaceIndex = null;

        for ($index = $previousEnd + 1; $index < $currentStart; $index++) {

            if ($tokens[ $index ]->isWhitespace() === false) {
                continue;
            }

            $lineBreaks += substr_count($tokens[ $index ]->getContent(), "\n");
            $whitespaceIndex ??= $index;

        }

        if ($lineBreaks >= 2) {
            return;
        }

        $lineEnding = $this->whitespacesConfig->getLineEnding();
        $missingLineBreaks = 2 - $lineBreaks;

        if ($whitespaceIndex === null) {

            $tokens->insertAt(
                $previousEnd + 1,
                new Token([
                    T_WHITESPACE,
                    sprintf(
                        '%s%s',
                        str_repeat($lineEnding, $missingLineBreaks),
                        $this->getLineIndentation($tokens, $currentStart),
                    ),
                ]),
            );

            return;

        }

        $tokens[ $whitespaceIndex ] = new Token([
            T_WHITESPACE,
            sprintf(
                '%s%s',
                str_repeat($lineEnding, $missingLineBreaks),
                $tokens[ $whitespaceIndex ]->getContent(),
            ),
        ]);
    }
}

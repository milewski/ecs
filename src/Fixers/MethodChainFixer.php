<?php

declare(strict_types = 1);

namespace Milewski\ECS\Fixers;

use Milewski\ECS\TokenAnalyzer\BooleanExpressionAnalyzer;
use Milewski\ECS\TokenAnalyzer\ControlStructureHeaderAnalyzer;
use Milewski\ECS\TokenAnalyzer\LineLengthAnalyzer;
use Milewski\ECS\TokenAnalyzer\SprintfCallAnalyzer;
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
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class MethodChainFixer extends AbstractFixer implements ConfigurableFixerInterface, WhitespacesAwareFixerInterface
{
    use ConfigurableFixerTrait;
    use IndentationTrait;

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            summary: 'Long chains of at least two method calls place each method on a separate line.',
            codeSamples: [
                new CodeSample(
                    code: "<?php\n\$repository->findAllMatchingRecords(\$criteria)->map(\$callback)->all();\n",
                    configuration: [ 'max_line_length' => 60 ],
                ),
            ],
        );
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([ T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR ]);
    }

    public function getPriority(): int
    {
        // Break chains before long argument lists and indentation are formatted.
        return 32;
    }

    protected function createConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        return new FixerConfigurationResolver([
            new FixerOptionBuilder('max_line_length', 'Wrap method chains whose lines exceed this limit.')
                ->setAllowedTypes([ 'int' ])
                ->setAllowedValues([ static fn (int $length): bool => $length > 0 ])
                ->setDefault(120)
                ->getOption(),
        ]);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        $booleanExpressions = new BooleanExpressionAnalyzer($tokens);

        for ($index = 1; $index < $tokens->count(); $index++) {

            $headerEnd = ControlStructureHeaderAnalyzer::findEnd($tokens, $index)
                ?? SprintfCallAnalyzer::findEnd($tokens, $index);

            if ($headerEnd !== null) {

                $index = $headerEnd;

                continue;

            }

            if ($tokens[ $index ]->isObjectOperator() === false
                || $booleanExpressions->contains($tokens[ $index ])
                || $this->methodParentheses($tokens, $index) === null
                || $this->continuesMethodChain($tokens, $index)) {
                continue;
            }

            $operators = [];
            $cursor = $index;

            while ($cursor !== null && $tokens[ $cursor ]->isObjectOperator()) {

                $openParenthesis = $this->methodParentheses($tokens, $cursor);

                if ($openParenthesis === null) {
                    break;
                }

                $operators[] = $cursor;
                $closeParenthesis = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openParenthesis);
                $cursor = $tokens->getNextMeaningfulToken($closeParenthesis);

            }

            if (count($operators) < 2 || $this->hasInlineLinks($tokens, $operators) === false) {
                continue;
            }

            $receiverStart = $this->receiverStart($tokens, $index);
            $indentation = $this->getLineIndentation($tokens, $receiverStart);

            if ($this->lineLength($tokens, $receiverStart, $closeParenthesis) <= $this->configuration[ 'max_line_length' ]) {
                continue;
            }

            $whitespace = $this->whitespacesConfig->getLineEnding()
                . $indentation
                . $this->whitespacesConfig->getIndent();

            for ($operatorIndex = count($operators) - 1; $operatorIndex >= 0; $operatorIndex--) {
                $tokens->ensureWhitespaceAtIndex($operators[ $operatorIndex ] - 1, 1, $whitespace);
            }

        }
    }

    /**
     * @param list<int> $operators
     */
    private function hasInlineLinks(Tokens $tokens, array $operators): bool
    {
        foreach (array_slice($operators, 1) as $operator) {

            $previous = $tokens->getPrevMeaningfulToken($operator);

            if ($tokens->isPartialCodeMultiline($previous, $operator) === false) {
                return true;
            }

        }

        return false;
    }

    private function lineLength(Tokens $tokens, int $start, int $end): int
    {
        for ($index = $start - 1; $index >= 0; $index--) {

            if ($tokens[ $index ]->equalsAny([ ';', '{' ])) {
                break;
            }

            $block = Tokens::detectBlockType($tokens[ $index ]);

            if ($block !== null && $block[ 'isStart' ] === false) {

                $open = $tokens->findBlockStart($block[ 'type' ], $index);

                $index = $open;

                continue;

            }

            if ($tokens[ $index ]->equals('(') === false) {
                continue;
            }

            $previous = $tokens->getPrevMeaningfulToken($index);

            if ($previous !== null && ($tokens[ $previous ]->isGivenKind([ T_STRING, T_VARIABLE, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE ]) || $tokens[ $previous ]->equalsAny([ ')', ']' ]))) {

                // The enclosing call can wrap its arguments without expanding this short nested chain.
                $lines = preg_split('/\R/', $tokens->generatePartialCode($start, $end));

                return max(array_map(strlen(...), $lines));

            }

        }

        return LineLengthAnalyzer::maximumLength($tokens, $start, $end);
    }

    private function methodParentheses(Tokens $tokens, int $operator): ?int
    {
        $name = $tokens->getNextMeaningfulToken($operator);

        if ($name === null || $tokens[ $name ]->isGivenKind([ T_STRING, T_VARIABLE ]) === false) {
            return null;
        }

        $openParenthesis = $tokens->getNextMeaningfulToken($name);

        return $openParenthesis !== null && $tokens[ $openParenthesis ]->equals('(') ? $openParenthesis : null;
    }

    private function continuesMethodChain(Tokens $tokens, int $operator): bool
    {
        $previous = $tokens->getPrevMeaningfulToken($operator);

        if ($previous === null || $tokens[ $previous ]->equals(')') === false) {
            return false;
        }

        $openParenthesis = $tokens->findBlockStart(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $previous);
        $name = $tokens->getPrevMeaningfulToken($openParenthesis);
        $previousOperator = $name === null ? null : $tokens->getPrevMeaningfulToken($name);

        return $previousOperator !== null && $tokens[ $previousOperator ]->isObjectOperator();
    }

    private function receiverStart(Tokens $tokens, int $operator): int
    {
        $start = $tokens->getPrevMeaningfulToken($operator);

        while (true) {

            $block = Tokens::detectBlockType($tokens[ $start ]);

            if ($block !== null && $block[ 'isStart' ] === false) {

                $start = $tokens->findBlockStart($block[ 'type' ], $start);
                $previous = $tokens->getPrevMeaningfulToken($start);

                if ($previous !== null && $tokens[ $previous ]->isGivenKind([ T_STRING, T_VARIABLE, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE, T_STATIC ])) {

                    $start = $previous;

                    continue;

                }

            }

            $previous = $tokens->getPrevMeaningfulToken($start);

            if ($previous !== null && ($tokens[ $previous ]->isObjectOperator() || $tokens[ $previous ]->isGivenKind(T_DOUBLE_COLON))) {

                $start = $tokens->getPrevMeaningfulToken($previous);

                continue;

            }

            if ($previous !== null && $tokens[ $previous ]->isGivenKind(T_NEW)) {
                $start = $previous;
            }

            return $start;

        }
    }
}

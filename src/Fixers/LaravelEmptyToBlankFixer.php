<?php

declare(strict_types = 1);

namespace Milewski\ECS\Fixers;

use Milewski\ECS\TokenAnalyzer\LaravelProjectDetector;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class LaravelEmptyToBlankFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'In Laravel projects, empty() must be replaced with blank() and !empty() with filled().',
            [],
        );
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_EMPTY);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        if (new LaravelProjectDetector()->supports($file) === false) {
            return;
        }

        foreach ($tokens as $index => $token) {

            if ($token->isGivenKind(T_EMPTY) === false) {
                continue;
            }

            $replacement = 'blank';
            $previous = $tokens->getPrevMeaningfulToken($index);

            if ($previous !== null && $tokens[ $previous ]->equals('!')) {

                $tokens->clearAt($previous);

                $this->clearWhitespaceBetween($tokens, $previous, $index);

                $replacement = 'filled';

            }

            $tokens[ $index ] = new Token([ T_STRING, $replacement ]);

        }
    }

    private function clearWhitespaceBetween(Tokens $tokens, int $start, int $end): void
    {
        for ($index = $start + 1; $index < $end; $index++) {

            if ($tokens[ $index ]->isWhitespace()) {
                $tokens->clearAt($index);
            }

        }
    }
}

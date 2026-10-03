<?php

declare(strict_types = 1);

namespace Milewski\ECS\Fixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\DocBlock\Annotation;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\DocBlock\TypeExpression;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class NoPointlessMixedPhpdocFixer extends AbstractFixer
{
    private const string POINTLESS_MIXED_TYPE_PATTERN = '/^(?:mixed|list<mixed>|array<(?:mixed|(?:array-key|int|string|mixed),mixed)>)$/';

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            summary: 'PHPDoc @param and @return annotations with no useful type information must be removed.',
            codeSamples: [],
        );
    }

    public function getPriority(): int
    {
        return 4;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_DOC_COMMENT);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $index => $token) {

            if ($token->isGivenKind(T_DOC_COMMENT) === false) {
                continue;
            }

            $docblock = new DocBlock($token->getContent());
            $changed = false;

            foreach ($docblock->getAnnotations() as $annotation) {

                $tag = strtolower($annotation->getTag()->getName());

                if (in_array($tag, [ 'param', 'return' ], true) === false
                    || $this->isPointlessMixedAnnotation($annotation) === false) {
                    continue;
                }

                $annotation->remove();

                $changed = true;

            }

            if ($changed) {
                $tokens[ $index ] = new Token([ T_DOC_COMMENT, $docblock->getContent() ]);
            }

        }
    }

    private function isPointlessMixedAnnotation(Annotation $annotation): bool
    {
        if (strtolower($annotation->getTag()->getName()) === 'param') {

            $pattern = '{\*\h*@param(?:\h+' . TypeExpression::REGEX_TYPES . ')?(?!\S)(?:\h+(?:\&\h*)?(?:\.{3}\h*)?\$\S+)?(?:\s+(?<description>(?!\*+\/)\S+))?}is';

        } else {

            $pattern = '{\*\h*@return(?:\h+' . TypeExpression::REGEX_TYPES . ')?(?!\S)(?:\s+(?<description>(?!\*\/)\S+))?}is';

        }

        if (preg_match($pattern, $annotation->getContent(), $matches) !== 1
            || isset($matches[ 'description' ])
            || isset($matches[ 'types' ]) === false) {
            return false;
        }

        $type = preg_replace('/[\s*]+/', '', strtolower($matches[ 'types' ]));

        return $type !== null && preg_match(self::POINTLESS_MIXED_TYPE_PATTERN, $type) === 1;
    }
}

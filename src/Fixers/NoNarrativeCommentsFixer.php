<?php

declare(strict_types = 1);

namespace Milewski\ECS\Fixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PHPStan\PhpDocParser\Ast\NodeTraverser;
use PHPStan\PhpDocParser\Ast\NodeVisitor\CloningVisitor;
use PHPStan\PhpDocParser\Ast\PhpDoc\GenericTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocChildNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use PHPStan\PhpDocParser\Printer\Printer;
use RuntimeException;
use SplFileInfo;

final class NoNarrativeCommentsFixer extends AbstractFixer
{
    private readonly Lexer $lexer;

    private readonly PhpDocParser $phpDocParser;

    public function __construct()
    {
        parent::__construct();

        $configuration = new ParserConfig([ 'lines' => true, 'indexes' => true, 'comments' => true ]);
        $constantExpressionParser = new ConstExprParser($configuration);
        $typeParser = new TypeParser($configuration, $constantExpressionParser);

        $this->lexer = new Lexer($configuration);
        $this->phpDocParser = new PhpDocParser($configuration, $typeParser, $constantExpressionParser);
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            summary: 'Remove narrative comments and PHPDoc prose while retaining structured type metadata.',
            codeSamples: [],
        );
    }

    public function getPriority(): int
    {
        return 1000;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([ T_COMMENT, T_DOC_COMMENT ]);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $index => $token) {

            if ($token->isGivenKind(T_COMMENT)) {

                if ($this->isMachineDirective($token->getContent())) {

                    $tokens[ $index ] = new Token([ T_COMMENT, $this->directiveMetadata($token->getContent()) ]);
                    continue;

                }

                $comment = preg_replace('/^(?:\/\/|#|\/\*)/', '/**', $token->getContent()) ?? $token->getContent();

                if (str_ends_with($comment, '*/') === false) {
                    $comment .= ' */';
                }

                $metadata = $this->metadata($comment, $file);

                if ($metadata !== '') {

                    $tokens[ $index ] = new Token([ T_DOC_COMMENT, $metadata ]);
                    continue;

                }

                $tokens[ $index ] = new Token([ T_WHITESPACE, $this->commentWhitespace($token->getContent()) ]);
                continue;

            }

            if ($token->isGivenKind(T_DOC_COMMENT) === false) {
                continue;
            }

            $replacement = $this->metadata($token->getContent(), $file);

            if ($replacement === '') {

                $tokens[ $index ] = new Token([ T_WHITESPACE, $this->commentWhitespace($token->getContent()) ]);
                continue;

            }

            $tokens[ $index ] = new Token([ T_DOC_COMMENT, $replacement ]);

        }

        for ($index = $tokens->count() - 1; $index > 0; $index--) {

            if ($tokens[ $index ]->isWhitespace() === false) {
                continue;
            }

            if ($tokens[ $index - 1 ]->isWhitespace() === false) {
                continue;
            }

            $tokens[ $index - 1 ] = new Token([ T_WHITESPACE, $tokens[ $index - 1 ]->getContent() . $tokens[ $index ]->getContent() ]);
            $tokens->clearAt($index);

        }

        $tokens->clearEmptyTokens();
    }

    private function metadata(string $comment, SplFileInfo $file): string
    {
        $originalTokens = new TokenIterator($this->lexer->tokenize($comment));
        $originalDocument = $this->phpDocParser->parse($originalTokens);
        $document = new NodeTraverser([ new CloningVisitor() ])->traverse([ $originalDocument ])[ 0 ];
        $annotations = [];

        foreach ($document->children as $child) {

            if ($child instanceof PhpDocTagNode === false) {

                if ($child instanceof PhpDocTextNode) {

                    if (trim($child->text) === '') {
                        $annotations[] = $child;
                    }

                }

                continue;

            }

            if ($child->value instanceof InvalidTagValueNode) {

                throw new RuntimeException(
                    sprintf('Invalid PHPDoc metadata in %s: %s.', $file->getPathname(), $child->name),
                );

            }

            if ($child->value instanceof GenericTagValueNode) {

                if ($this->isMachineDirective($child->name)) {

                    $child->value->value = $this->directiveMetadata($child->value->value);
                    $annotations[] = $child;
                    continue;

                }

                if (in_array($child->name, [ '@internal', '@api', '@deprecated', '@readonly', '@immutable', '@final', '@pure', '@phpstan-pure', '@phpstan-impure', '@psalm-pure', '@psalm-immutable', '@no-named-arguments' ], true) === false) {
                    continue;
                }

                $child->value->value = '';

            }

            if (property_exists($child->value, 'description')) {
                $child->value->description = '';
            }

            $annotations[] = $child;

        }

        if (array_any($annotations, static fn (PhpDocChildNode $annotation): bool => $annotation instanceof PhpDocTagNode) === false) {
            return '';
        }

        $document->children = $annotations;

        return new Printer()->printFormatPreserving($document, $originalDocument, $originalTokens);
    }

    private function commentWhitespace(string $comment): string
    {
        $lineBreaks = preg_replace('/[^\r\n]/', '', $comment) ?? '';

        if ($lineBreaks === '') {
            return ' ';
        }

        return $lineBreaks;
    }

    private function isMachineDirective(string $comment): bool
    {
        return preg_match('/^(?:\/\/|#|\/\*+)?\s*(?:@(?:phpstan-ignore(?:-line|-next-line)?|psalm-suppress|noinspection|codeCoverageIgnore(?:Start|End)?)\b|phpcs:(?:ignore|disable|enable)\b)/', $comment) === 1;
    }

    private function directiveMetadata(string $directive): string
    {
        $directive = preg_replace('/\s+--[^\r\n]*?(?=\s*\*\/|[\r\n]|$)/', '', $directive) ?? $directive;

        return preg_replace('/\s*\([^()]*\)/', '', $directive) ?? $directive;
    }
}

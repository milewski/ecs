<?php

declare(strict_types = 1);

namespace Milewski\ECS\Fixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\StaticVar;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Global_;
use PhpParser\Node\UnionType;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use SplFileInfo;

final class DescriptiveVariableNameFixer extends AbstractFixer
{
    /**
     * Types that do not carry enough meaning for a descriptive variable name.
     *
     * @var list<string>
     */
    private const array IGNORED_TYPE_NAMES = [
        'false',
        'mixed',
        'never',
        'null',
        'parent',
        'self',
        'static',
        'true',
        'void',
    ];

    /**
     * Generic class suffixes that describe the declaration rather than the value.
     *
     * @var list<string>
     */
    private const array TYPE_SUFFIXES = [
        'contract',
        'interface',
    ];

    private readonly Parser $parser;

    public function __construct()
    {
        parent::__construct();

        $this->parser = (new ParserFactory())->createForNewestSupportedVersion();
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Typed function parameters must use descriptive names inferred from their types.',
            [
                new CodeSample(<<<'PHP'
                <?php

                array_map(static fn (Permission $p): string => $p->ability(), $permissions);
                PHP),
            ],
            null,
            'Renaming a function parameter can affect calls that use named arguments or reflection.',
        );
    }

    public function getPriority(): int
    {
        // Resolve parameter names before calls are wrapped and converted to named arguments.
        return 50;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_VARIABLE)
            && $tokens->isAnyTokenKindsFound([ T_FN, T_FUNCTION ]);
    }

    public function isRisky(): bool
    {
        return true;
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        $code = $tokens->generateCode();

        try {

            $statements = $this->parser->parse($code);

            if ($statements === null) {
                return;
            }

            $statements = (new NodeTraverser(new NameResolver(options: [ 'replaceNodes' => false ])))
                ->traverse($statements);

        } catch (Error) {

            return;

        }

        /**
         * @var array<int, array{end: int, replacement: string}> $edits
         */
        $edits = [];

        /**
         * @var array<int, true> $conflicts
         */
        $conflicts = [];

        $this->collectEdits($statements, $edits, $conflicts);

        krsort($edits);

        foreach ($edits as $start => $edit) {

            $code = substr_replace(
                $code,
                $edit[ 'replacement' ],
                $start,
                $edit[ 'end' ] - $start + 1,
            );

        }

        $tokens->setCode($code);
    }

    /**
     * @param array<Node>|Node|null $node
     * @param array<int, array{end: int, replacement: string}> $edits
     * @param array<int, true> $conflicts
     */
    private function collectEdits(Node|array|null $node, array &$edits, array &$conflicts): void
    {
        if ($node instanceof FunctionLike) {
            $this->collectFunctionEdits($node, $edits, $conflicts);
        }

        if (is_array($node)) {

            foreach ($node as $child) {
                $this->collectEdits($child, $edits, $conflicts);
            }

            return;

        }

        if ($node === null) {
            return;
        }

        foreach ($node->getSubNodeNames() as $subNodeName) {

            $subNode = $node->{$subNodeName};

            if ($subNode instanceof Node || is_array($subNode)) {
                $this->collectEdits($subNode, $edits, $conflicts);
            }

        }
    }

    /**
     * @param array<int, array{end: int, replacement: string}> $edits
     * @param array<int, true> $conflicts
     */
    private function collectFunctionEdits(FunctionLike $function, array &$edits, array &$conflicts): void
    {
        /**
         * @var list<array{param: Param, current: string, inferred: array{name: string, abbreviations: list<string>}}> $candidates
         */
        $candidates = [];
        $inferredNameCounts = [];

        foreach ($function->getParams() as $param) {

            if (!$param->var instanceof Variable
                || !is_string($param->var->name)
                || $param->type === null
                || $param->isPromoted()) {
                continue;
            }

            $inferred = $this->inferVariableName($param->type);
            $current = $param->var->name;

            if ($inferred === null || !$this->isAbbreviation($current, $inferred)) {
                continue;
            }

            $candidates[] = [
                'param' => $param,
                'current' => $current,
                'inferred' => $inferred,
            ];

            $inferredNameCounts[ $inferred[ 'name' ] ] = ($inferredNameCounts[ $inferred[ 'name' ] ] ?? 0) + 1;

        }

        foreach ($candidates as $candidate) {

            $current = $candidate[ 'current' ];
            $inferredName = $candidate[ 'inferred' ][ 'name' ];

            if ($inferredNameCounts[ $inferredName ] > 1
                || $this->containsVariableNamed($function, $inferredName)
                || $this->containsConflictingBinding($function, $current)) {
                continue;
            }

            $variables = [ $candidate[ 'param' ]->var ];

            if ($function instanceof ArrowFunction) {

                $this->collectBindingVariables($function->expr, $current, $variables);

            } else {

                $this->collectBindingVariables($function->getStmts(), $current, $variables);

            }

            foreach ($variables as $variable) {
                $this->addEdit($variable, $inferredName, $edits, $conflicts);
            }

        }
    }

    /**
     * @return array{name: string, abbreviations: list<string>}|null
     */
    private function inferVariableName(Node $type): ?array
    {
        if ($type instanceof NullableType) {
            return $this->inferVariableName($type->type);
        }

        if ($type instanceof UnionType || $type instanceof IntersectionType) {

            $inferences = [];

            foreach ($type->types as $memberType) {

                if ($memberType instanceof Identifier && strtolower($memberType->name) === 'null') {
                    continue;
                }

                $inference = $this->inferVariableName($memberType);

                if ($inference === null) {
                    return null;
                }

                $inferences[] = $inference;

            }

            $names = array_unique(array_column($inferences, 'name'));

            if (count($names) !== 1) {
                return null;
            }

            return [
                'name' => reset($names),
                'abbreviations' => array_values(array_unique(array_merge(
                    ...array_column($inferences, 'abbreviations'),
                ))),
            ];

        }

        if ($type instanceof Identifier) {
            return $this->inferFromTypeName($type->name);
        }

        if (!$type instanceof Name) {
            return null;
        }

        $resolvedName = $type->getAttribute('resolvedName');
        $typeName = $resolvedName instanceof Name ? $resolvedName->toString() : $type->toString();

        return $this->inferFromTypeName($typeName);
    }

    /**
     * @return array{name: string, abbreviations: list<string>}|null
     */
    private function inferFromTypeName(string $typeName): ?array
    {
        $typeNameParts = explode('\\', $typeName);
        $shortTypeName = end($typeNameParts);
        $normalizedTypeName = strtolower($shortTypeName);

        if (in_array($normalizedTypeName, self::IGNORED_TYPE_NAMES, true)) {
            return null;
        }

        $words = preg_split(
            '/(?<=[a-z0-9])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z])|[_-]+/',
            $shortTypeName,
        );

        if ($words === false || $words === []) {
            return null;
        }

        while (count($words) > 1 && in_array(strtolower(end($words)), self::TYPE_SUFFIXES, true)) {
            array_pop($words);
        }

        $name = strtolower(end($words));
        $initials = implode('', array_map(
            static fn (string $word): string => strtolower($word[ 0 ]),
            $words,
        ));

        return [
            'name' => $name,
            'abbreviations' => [ $initials ],
        ];
    }

    /**
     * @param array{name: string, abbreviations: list<string>} $inferred
     */
    private function isAbbreviation(string $current, array $inferred): bool
    {
        $current = strtolower($current);
        $expected = $inferred[ 'name' ];

        if ($current === $expected) {
            return false;
        }

        if (strlen($current) === 1 || in_array($current, $inferred[ 'abbreviations' ], true)) {
            return true;
        }

        if (strlen($current) >= strlen($expected)) {
            return false;
        }

        if (str_starts_with($expected, $current)) {
            return true;
        }

        return preg_match('/[aeiou]/', $current) === 0
            && $this->isOrderedSubsequence($current, $expected);
    }

    private function isOrderedSubsequence(string $abbreviation, string $word): bool
    {
        $abbreviationIndex = 0;
        $abbreviationLength = strlen($abbreviation);

        for ($wordIndex = 0, $wordLength = strlen($word); $wordIndex < $wordLength; $wordIndex++) {

            if ($word[ $wordIndex ] !== $abbreviation[ $abbreviationIndex ]) {
                continue;
            }

            $abbreviationIndex++;

            if ($abbreviationIndex === $abbreviationLength) {
                return true;
            }

        }

        return false;
    }

    /**
     * @param array<Node>|Node|null $node
     */
    private function containsVariableNamed(Node|array|null $node, string $name): bool
    {
        if ($node instanceof Variable && $node->name === $name) {
            return true;
        }

        if (is_array($node)) {

            foreach ($node as $child) {

                if ($this->containsVariableNamed($child, $name)) {
                    return true;
                }

            }

            return false;

        }

        if ($node === null) {
            return false;
        }

        foreach ($node->getSubNodeNames() as $subNodeName) {

            $subNode = $node->{$subNodeName};

            if (($subNode instanceof Node || is_array($subNode))
                && $this->containsVariableNamed($subNode, $name)) {
                return true;
            }

        }

        return false;
    }

    /**
     * @param array<Node>|Node|null $node
     */
    private function containsConflictingBinding(Node|array|null $node, string $name): bool
    {
        if ($node instanceof Catch_ && $node->var?->name === $name) {
            return true;
        }

        if ($node instanceof Global_) {

            foreach ($node->vars as $variable) {

                if ($variable->name === $name) {
                    return true;
                }

            }

        }

        if ($node instanceof StaticVar && $node->var->name === $name) {
            return true;
        }

        if (is_array($node)) {

            foreach ($node as $child) {

                if ($this->containsConflictingBinding($child, $name)) {
                    return true;
                }

            }

            return false;

        }

        if ($node === null) {
            return false;
        }

        foreach ($node->getSubNodeNames() as $subNodeName) {

            $subNode = $node->{$subNodeName};

            if (($subNode instanceof Node || is_array($subNode))
                && $this->containsConflictingBinding($subNode, $name)) {
                return true;
            }

        }

        return false;
    }

    /**
     * @param array<Node>|Node|null $node
     * @param list<Variable> $variables
     */
    private function collectBindingVariables(Node|array|null $node, string $name, array &$variables): void
    {
        if ($node instanceof Variable && $node->name === $name) {

            $variables[] = $node;

            return;

        }

        if ($node instanceof ArrowFunction) {

            if ($this->functionDeclaresVariable($node, $name)) {
                return;
            }

            $this->collectBindingVariables($node->expr, $name, $variables);

            return;

        }

        if ($node instanceof Closure) {

            if ($this->functionDeclaresVariable($node, $name)) {
                return;
            }

            foreach ($node->uses as $use) {

                if ($use->var->name !== $name) {
                    continue;
                }

                $variables[] = $use->var;

                $this->collectBindingVariables($node->stmts, $name, $variables);

                return;

            }

            return;

        }

        if ($node instanceof Function_ || $node instanceof ClassMethod || $node instanceof ClassLike) {
            return;
        }

        if ($node instanceof Catch_ && $node->var?->name === $name) {
            return;
        }

        if (is_array($node)) {

            foreach ($node as $child) {
                $this->collectBindingVariables($child, $name, $variables);
            }

            return;

        }

        if ($node === null) {
            return;
        }

        foreach ($node->getSubNodeNames() as $subNodeName) {

            $subNode = $node->{$subNodeName};

            if ($subNode instanceof Node || is_array($subNode)) {
                $this->collectBindingVariables($subNode, $name, $variables);
            }

        }
    }

    private function functionDeclaresVariable(FunctionLike $function, string $name): bool
    {
        foreach ($function->getParams() as $param) {

            if ($param->var instanceof Variable && $param->var->name === $name) {
                return true;
            }

        }

        return false;
    }

    /**
     * @param array<int, array{end: int, replacement: string}> $edits
     * @param array<int, true> $conflicts
     */
    private function addEdit(Variable $variable, string $name, array &$edits, array &$conflicts): void
    {
        $start = $variable->getStartFilePos();
        $end = $variable->getEndFilePos();

        if ($start < 0 || $end < $start || isset($conflicts[ $start ])) {
            return;
        }

        $replacement = sprintf('$%s', $name);

        if (isset($edits[ $start ]) && $edits[ $start ][ 'replacement' ] !== $replacement) {

            unset($edits[ $start ]);

            $conflicts[ $start ] = true;

            return;

        }

        $edits[ $start ] = [
            'end' => $end,
            'replacement' => $replacement,
        ];
    }
}

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
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use SplFileInfo;

final class SnakeCaseGlobalFunctionNameFixer extends AbstractFixer
{
    private readonly Parser $parser;

    public function __construct()
    {
        parent::__construct();

        $this->parser = new ParserFactory()->createForNewestSupportedVersion();
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            summary: 'Functions declared outside classes must use snake_case names.',
            codeSamples: [
                new CodeSample(<<<'PHP'
                <?php

                function formatUserName(): string
                {
                    return 'Taylor Otwell';
                }
                PHP),
            ],
            description: null,
            riskyDescription: 'Renaming a global function can affect references in other files, string callables, or reflection.',
        );
    }

    public function getPriority(): int
    {
        // Finalize callable names before measuring call line lengths.
        return 45;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_FUNCTION);
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

            $statements = new NodeTraverser(new NameResolver(options: [ 'replaceNodes' => false ]))
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

        $renames = $this->collectFunctionRenames($statements, $edits, $conflicts);

        if ($renames === []) {
            return;
        }

        /**
         * @var array<string, true> $explicitAliases
         */
        $explicitAliases = [];

        $this->collectImportEdits($statements, '', $renames, $explicitAliases, $edits, $conflicts);
        $this->collectCallEdits($statements, '', $renames, $explicitAliases, $code, $edits, $conflicts);

        krsort($edits);

        foreach ($edits as $start => $edit) {

            $code = substr_replace(
                string: $code,
                replace: $edit[ 'replacement' ],
                offset: $start,
                length: $edit[ 'end' ] - $start + 1,
            );

        }

        $tokens->setCode($code);
    }

    /**
     * @param array<Node> $statements
     * @param array<int, array{end: int, replacement: string}> $edits
     * @param array<int, true> $conflicts
     *
     * @return array<string, array{new: string}>
     */
    private function collectFunctionRenames(array $statements, array &$edits, array &$conflicts): array
    {
        $functions = new NodeFinder()->findInstanceOf($statements, Function_::class);
        $declaredFunctions = [];
        $candidates = [];
        $targetCounts = [];

        foreach ($functions as $function) {

            $oldName = $function->name->toString();
            $newName = $this->snakeCase($oldName);
            $qualifiedName = isset($function->namespacedName)
                ? $function->namespacedName->toString()
                : $oldName;

            $qualifiedKey = strtolower($qualifiedName);

            $declaredFunctions[ $qualifiedKey ] = true;

            if ($oldName === $newName) {
                continue;
            }

            $targetKey = strtolower($this->replaceLastSegment($qualifiedName, $newName));
            $targetCounts[ $targetKey ] = ($targetCounts[ $targetKey ] ?? 0) + 1;
            $candidates[] = [
                'function' => $function,
                'qualified_key' => $qualifiedKey,
                'target_key' => $targetKey,
                'new' => $newName,
            ];

        }

        $renames = [];

        foreach ($candidates as $candidate) {

            if ($targetCounts[ $candidate[ 'target_key' ] ] > 1
                || ($candidate[ 'target_key' ] !== $candidate[ 'qualified_key' ]
                    && isset($declaredFunctions[ $candidate[ 'target_key' ] ]))) {
                continue;
            }

            $function = $candidate[ 'function' ];

            $this->addNodeEdit($function->name, $candidate[ 'new' ], $edits, $conflicts);

            $renames[ $candidate[ 'qualified_key' ] ] = [
                'new' => $candidate[ 'new' ],
            ];

        }

        return $renames;
    }

    /**
     * @param array<Node>|Node|null $node
     * @param array<string, array{new: string}> $renames
     * @param array<string, true> $explicitAliases
     * @param array<int, array{end: int, replacement: string}> $edits
     * @param array<int, true> $conflicts
     */
    private function collectImportEdits(Node|array|null $node, string $namespace, array $renames, array &$explicitAliases, array &$edits, array &$conflicts): void
    {
        if (is_array($node)) {

            foreach ($node as $child) {
                $this->collectImportEdits($child, $namespace, $renames, $explicitAliases, $edits, $conflicts);
            }

            return;

        }

        if ($node === null) {
            return;
        }

        if ($node instanceof Namespace_) {

            $namespace = $node->name?->toString() ?? '';

            $this->collectImportEdits($node->stmts, $namespace, $renames, $explicitAliases, $edits, $conflicts);

            return;

        }

        if ($node instanceof Use_) {

            foreach ($node->uses as $use) {

                $type = $use->type === Use_::TYPE_UNKNOWN ? $node->type : $use->type;

                $this->collectUseItemEdit($use, null, $type, $namespace, $renames, $explicitAliases, $edits, $conflicts);

            }

            return;

        }

        if ($node instanceof GroupUse) {

            foreach ($node->uses as $use) {

                $type = $use->type === Use_::TYPE_UNKNOWN ? $node->type : $use->type;

                $this->collectUseItemEdit($use, $node->prefix, $type, $namespace, $renames, $explicitAliases, $edits, $conflicts);

            }

            return;

        }

        foreach ($node->getSubNodeNames() as $subNodeName) {

            $subNode = $node->{$subNodeName};

            if ($subNode instanceof Node || is_array($subNode)) {
                $this->collectImportEdits($subNode, $namespace, $renames, $explicitAliases, $edits, $conflicts);
            }

        }
    }

    /**
     * @param array<string, array{new: string}> $renames
     * @param array<string, true> $explicitAliases
     * @param array<int, array{end: int, replacement: string}> $edits
     * @param array<int, true> $conflicts
     */
    private function collectUseItemEdit(UseItem $use, ?Name $prefix, int $type, string $namespace, array $renames, array &$explicitAliases, array &$edits, array &$conflicts): void
    {
        if ($type !== Use_::TYPE_FUNCTION) {
            return;
        }

        $qualifiedName = $prefix === null
            ? $use->name->toString()
            : sprintf('%s\\%s', $prefix->toString(), $use->name->toString());

        $rename = $renames[ strtolower($qualifiedName) ] ?? null;

        if ($rename === null) {
            return;
        }

        $this->addNameEdit($use->name, $rename[ 'new' ], $edits, $conflicts);

        if ($use->alias !== null) {
            $explicitAliases[ strtolower($this->qualify($namespace, $use->alias->toString())) ] = true;
        }
    }

    /**
     * @param array<Node>|Node|null $node
     * @param array<string, array{new: string}> $renames
     * @param array<string, true> $explicitAliases
     * @param array<int, array{end: int, replacement: string}> $edits
     * @param array<int, true> $conflicts
     */
    private function collectCallEdits(Node|array|null $node, string $namespace, array $renames, array $explicitAliases, string $code, array &$edits, array &$conflicts): void
    {
        if (is_array($node)) {

            foreach ($node as $child) {
                $this->collectCallEdits($child, $namespace, $renames, $explicitAliases, $code, $edits, $conflicts);
            }

            return;

        }

        if ($node === null) {
            return;
        }

        if ($node instanceof Namespace_) {

            $namespace = $node->name?->toString() ?? '';

            $this->collectCallEdits($node->stmts, $namespace, $renames, $explicitAliases, $code, $edits, $conflicts);

            return;

        }

        if ($node instanceof FuncCall && $node->name instanceof Name) {

            $this->collectFunctionExistsEdit($node, $renames, $code, $edits, $conflicts);

            $rawLastSegment = $node->name->getLast();
            $isExplicitAlias = $node->name->isUnqualified()
                && isset($explicitAliases[ strtolower($this->qualify($namespace, $rawLastSegment)) ]);

            $qualifiedName = $this->resolveCallName($node->name, $namespace, $renames);
            $rename = $renames[ strtolower($qualifiedName) ] ?? null;

            if ($rename !== null && $isExplicitAlias === false) {
                $this->addNameEdit($node->name, $rename[ 'new' ], $edits, $conflicts);
            }

        }

        foreach ($node->getSubNodeNames() as $subNodeName) {

            $subNode = $node->{$subNodeName};

            if ($subNode instanceof Node || is_array($subNode)) {
                $this->collectCallEdits($subNode, $namespace, $renames, $explicitAliases, $code, $edits, $conflicts);
            }

        }
    }

    /**
     * @param array<string, array{new: string}> $renames
     * @param array<int, array{end: int, replacement: string}> $edits
     * @param array<int, true> $conflicts
     */
    private function collectFunctionExistsEdit(FuncCall $call, array $renames, string $code, array &$edits, array &$conflicts): void
    {
        if (!$call->name instanceof Name
            || strtolower($call->name->toString()) !== 'function_exists'
            || !isset($call->args[ 0 ])
            || !$call->args[ 0 ]->value instanceof String_) {
            return;
        }

        $literal = $call->args[ 0 ]->value;
        $qualifiedName = ltrim($literal->value, '\\');
        $rename = $renames[ strtolower($qualifiedName) ] ?? null;

        if ($rename === null) {
            return;
        }

        $replacement = $this->replaceLastSegment($literal->value, $rename[ 'new' ]);
        $rawLiteral = substr($code, $literal->getStartFilePos(), $literal->getEndFilePos() - $literal->getStartFilePos() + 1);

        $quote = $rawLiteral[ 0 ] ?? '';

        if (($quote !== '\'' && $quote !== '"') || $rawLiteral[ -1 ] !== $quote) {
            return;
        }

        $replacement = $quote . str_replace('\\', '\\\\', $replacement) . $quote;

        $this->addEdit(
            start: $literal->getStartFilePos(),
            end: $literal->getEndFilePos(),
            replacement: $replacement,
            edits: $edits,
            conflicts: $conflicts,
        );
    }

    /**
     * @param array<string, array{new: string}> $renames
     */
    private function resolveCallName(Name $name, string $namespace, array $renames): string
    {
        $resolvedName = $name->getAttribute('resolvedName');

        if ($resolvedName instanceof Name) {
            return $resolvedName->toString();
        }

        if (!$name->isUnqualified()) {
            return $this->qualify($namespace, $name->toString());
        }

        $namespacedName = $this->qualify($namespace, $name->toString());

        if (isset($renames[ strtolower($namespacedName) ])) {
            return $namespacedName;
        }

        return $name->toString();
    }

    private function snakeCase(string $name): string
    {
        $name = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1_$2', $name) ?? $name;
        $name = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $name) ?? $name;

        return strtolower($name);
    }

    private function replaceLastSegment(string $name, string $replacement): string
    {
        $separator = strrpos($name, '\\');

        if ($separator === false) {
            return $replacement;
        }

        return substr($name, 0, $separator + 1) . $replacement;
    }

    private function qualify(string $namespace, string $name): string
    {
        return $namespace === '' ? ltrim($name, '\\') : sprintf('%s\\%s', $namespace, ltrim($name, '\\'));
    }

    /**
     * @param array<int, array{end: int, replacement: string}> $edits
     * @param array<int, true> $conflicts
     */
    private function addNameEdit(Name $name, string $replacement, array &$edits, array &$conflicts): void
    {
        $lastSegment = $name->getLast();
        $end = $name->getEndFilePos();
        $start = $end - strlen($lastSegment) + 1;

        $this->addEdit($start, $end, $replacement, $edits, $conflicts);
    }

    /**
     * @param array<int, array{end: int, replacement: string}> $edits
     * @param array<int, true> $conflicts
     */
    private function addNodeEdit(Identifier $identifier, string $replacement, array &$edits, array &$conflicts): void
    {
        $this->addEdit(
            start: $identifier->getStartFilePos(),
            end: $identifier->getEndFilePos(),
            replacement: $replacement,
            edits: $edits,
            conflicts: $conflicts,
        );
    }

    /**
     * @param array<int, array{end: int, replacement: string}> $edits
     * @param array<int, true> $conflicts
     */
    private function addEdit(int $start, int $end, string $replacement, array &$edits, array &$conflicts): void
    {
        if ($start < 0 || $end < $start || isset($conflicts[ $start ])) {
            return;
        }

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

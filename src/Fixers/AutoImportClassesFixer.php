<?php

declare(strict_types = 1);

namespace Milewski\ECS\Fixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\Import\FullyQualifiedStrictTypesFixer;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Analyzer\Analysis\NamespaceAnalysis;
use PhpCsFixer\Tokenizer\Analyzer\NamespaceUsesAnalyzer;
use PhpCsFixer\Tokenizer\Processor\ImportProcessor;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class AutoImportClassesFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            summary: 'Fully qualified class references must be imported, with deterministic aliases for conflicting short names.',
            codeSamples: [
                new CodeSample(
                    <<<'PHP'
                    <?php

                    namespace App;

                    $items = \Illuminate\Database\Eloquent\Collection::new();

                    PHP,
                ),
            ],
        );
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([ T_NS_SEPARATOR, T_DOC_COMMENT ]);
    }

    public function getPriority(): int
    {
        return 6;
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        $fullyQualifiedStrictTypesFixer = new FullyQualifiedStrictTypesFixer();
        $fullyQualifiedStrictTypesFixer->setWhitespacesConfig($this->whitespacesConfig);
        $fullyQualifiedStrictTypesFixer->configure([ 'import_symbols' => true ]);
        $fullyQualifiedStrictTypesFixer->fix($file, $tokens);

        $candidates = $this->discoverUnresolvedClasses($tokens);

        if ($candidates === []) {
            return;
        }

        $aliases = $this->createAliases($tokens, $candidates);
        $probeTokens = Tokens::fromArray(iterator_to_array($tokens), false);

        $this->insertAliasImports($probeTokens, $aliases);

        $fullyQualifiedStrictTypesFixer->configure([ 'import_symbols' => false ]);
        $fullyQualifiedStrictTypesFixer->fix($file, $probeTokens);

        $usedAliases = $this->findUsedAliases($probeTokens, $aliases);

        if ($usedAliases === []) {
            return;
        }

        $this->insertAliasImports($tokens, $usedAliases);

        $fullyQualifiedStrictTypesFixer->fix($file, $tokens);
    }

    /**
     * @return array<int, array<string, non-empty-string>>
     */
    private function discoverUnresolvedClasses(Tokens $tokens): array
    {
        $candidates = [];
        $namespaceUsesAnalyzer = new NamespaceUsesAnalyzer();

        foreach (array_values($tokens->getNamespaceDeclarations()) as $namespaceIndex => $namespace) {

            $useRanges = $this->getUseRanges($tokens, $namespace, $namespaceUsesAnalyzer);

            for ($index = $namespace->getScopeStartIndex(); $index <= $namespace->getScopeEndIndex(); $index++) {

                if (!$tokens[ $index ]->isGivenKind(T_NS_SEPARATOR) || $this->isWithinRanges($index, $useRanges)) {
                    continue;
                }

                $previousIndex = $tokens->getPrevMeaningfulToken($index);

                if ($previousIndex !== null && $tokens[ $previousIndex ]->isGivenKind([ T_STRING, T_NS_SEPARATOR ])) {
                    continue;
                }

                $endIndex = $index;
                $segmentCount = 0;

                for ($cursor = $index + 1; $cursor <= $namespace->getScopeEndIndex(); $cursor++) {

                    $expectsString = $segmentCount === 0 || $tokens[ $endIndex ]->isGivenKind(T_NS_SEPARATOR);

                    if ($expectsString && $tokens[ $cursor ]->isGivenKind(T_STRING)) {

                        $segmentCount++;

                        $endIndex = $cursor;

                        continue;

                    }

                    if (!$expectsString && $tokens[ $cursor ]->isGivenKind(T_NS_SEPARATOR)) {

                        $endIndex = $cursor;

                        continue;

                    }

                    break;

                }

                if ($segmentCount < 2 || $tokens[ $endIndex ]->isGivenKind(T_NS_SEPARATOR)) {
                    continue;
                }

                $fullyQualifiedClass = ltrim($tokens->generatePartialCode($index, $endIndex), '\\');
                $candidates[ $namespaceIndex ][ strtolower($fullyQualifiedClass) ] = $fullyQualifiedClass;
                $index = $endIndex;

            }

        }

        return $candidates;
    }

    /**
     * @param array<int, array<string, non-empty-string>> $candidates
     *
     * @return array<int, array<non-empty-string, non-empty-string>>
     */
    private function createAliases(Tokens $tokens, array $candidates): array
    {
        $aliases = [];
        $namespaces = array_values($tokens->getNamespaceDeclarations());

        foreach ($candidates as $namespaceIndex => $classes) {

            $namespace = $namespaces[ $namespaceIndex ];
            $reservedIdentifiers = $this->collectReservedIdentifiers($tokens, $namespace);

            natcasesort($classes);

            foreach ($classes as $fullyQualifiedClass) {

                $alias = $this->createAlias($fullyQualifiedClass, $reservedIdentifiers);
                $aliases[ $namespaceIndex ][ $alias ] = $fullyQualifiedClass;
                $reservedIdentifiers[ strtolower($alias) ] = true;

            }

        }

        return $aliases;
    }

    /**
     * @return array<string, true>
     */
    private function collectReservedIdentifiers(Tokens $tokens, NamespaceAnalysis $namespace): array
    {
        $reservedIdentifiers = [];

        for ($index = $namespace->getScopeStartIndex(); $index <= $namespace->getScopeEndIndex(); $index++) {

            if ($tokens[ $index ]->isGivenKind(T_STRING)) {
                $reservedIdentifiers[ strtolower($tokens[ $index ]->getContent())] = true;
            }

        }

        return $reservedIdentifiers;
    }

    /**
     * @param array<string, true> $reservedIdentifiers
     *
     * @return non-empty-string
     */
    private function createAlias(string $fullyQualifiedClass, array $reservedIdentifiers): string
    {
        $segments = explode('\\', $fullyQualifiedClass);
        $shortName = array_pop($segments);
        $namespacePrefix = '';
        $candidate = $shortName;

        while ($segments !== []) {

            $namespacePrefix = sprintf('%s%s', array_pop($segments), $namespacePrefix);
            $candidate = sprintf('%s%s', $namespacePrefix, $shortName);

            if (!isset($reservedIdentifiers[ strtolower($candidate) ])) {
                return $candidate;
            }

        }

        $suffix = 2;

        while (isset($reservedIdentifiers[ strtolower(sprintf('%s%d', $candidate, $suffix)) ])) {
            $suffix++;
        }

        return sprintf('%s%d', $candidate, $suffix);
    }

    /**
     * @param array<int, array<non-empty-string, non-empty-string>> $aliases
     */
    private function insertAliasImports(Tokens $tokens, array $aliases): void
    {
        $namespaces = array_values($tokens->getNamespaceDeclarations());
        $namespaceUsesAnalyzer = new NamespaceUsesAnalyzer();
        $namespaceIndexes = array_keys($aliases);

        rsort($namespaceIndexes);

        foreach ($namespaceIndexes as $namespaceIndex) {

            if ($aliases[ $namespaceIndex ] === []) {
                continue;
            }

            $namespace = $namespaces[ $namespaceIndex ];
            $atIndex = $this->determineImportIndex($tokens, $namespace, $namespaceUsesAnalyzer);

            $this->insertImportsAt($tokens, $aliases[ $namespaceIndex ], $atIndex);

        }
    }

    private function determineImportIndex(Tokens $tokens, NamespaceAnalysis $namespace, NamespaceUsesAnalyzer $namespaceUsesAnalyzer): int
    {
        $lastUseEndIndex = null;

        foreach ($namespaceUsesAnalyzer->getDeclarationsInNamespace($tokens, $namespace, true) as $use) {
            $lastUseEndIndex = max($lastUseEndIndex ?? 0, $use->getEndIndex());
        }

        if ($lastUseEndIndex !== null) {
            return $lastUseEndIndex + 1;
        }

        if ($namespace->getEndIndex() !== 0) {
            return $namespace->getEndIndex() + 1;
        }

        $firstTokenIndex = $tokens->getNextMeaningfulToken($namespace->getScopeStartIndex());

        if ($firstTokenIndex !== null && $tokens[ $firstTokenIndex ]->isGivenKind(T_DECLARE)) {

            $declareEndIndex = $tokens->getNextTokenOfKind($firstTokenIndex, [ ';' ]);

            return $declareEndIndex + 1;

        }

        return $namespace->getScopeStartIndex() + 1;
    }

    /**
     * @param array<non-empty-string, non-empty-string> $aliases
     */
    private function insertImportsAt(Tokens $tokens, array $aliases, int $atIndex): void
    {
        $lineEnding = $this->whitespacesConfig->getLineEnding();

        if (!$tokens[ $atIndex ]->isWhitespace() || !str_contains($tokens[ $atIndex ]->getContent(), "\n")) {
            $tokens->insertAt($atIndex, new Token([ T_WHITESPACE, $lineEnding ]));
        }

        natcasesort($aliases);

        $items = [];

        foreach ($aliases as $alias => $fullyQualifiedClass) {

            $items[] = new Token([ T_WHITESPACE, $lineEnding ]);
            $items[] = new Token([ T_USE, 'use' ]);
            $items[] = new Token([ T_WHITESPACE, ' ' ]);

            array_push($items, ...ImportProcessor::tokenizeName($fullyQualifiedClass));

            $items[] = new Token([ T_WHITESPACE, ' ' ]);
            $items[] = new Token([ T_AS, 'as' ]);
            $items[] = new Token([ T_WHITESPACE, ' ' ]);
            $items[] = new Token([ T_STRING, $alias ]);
            $items[] = new Token(';');

        }

        $tokens->insertAt($atIndex, $items);
    }

    /**
     * @param array<int, array<non-empty-string, non-empty-string>> $aliases
     *
     * @return array<int, array<non-empty-string, non-empty-string>>
     */
    private function findUsedAliases(Tokens $tokens, array $aliases): array
    {
        $usedAliases = [];
        $namespaces = array_values($tokens->getNamespaceDeclarations());
        $namespaceUsesAnalyzer = new NamespaceUsesAnalyzer();

        foreach ($aliases as $namespaceIndex => $namespaceAliases) {

            $namespace = $namespaces[ $namespaceIndex ];
            $useRanges = $this->getUseRanges($tokens, $namespace, $namespaceUsesAnalyzer);
            $aliasesByLowercaseName = [];

            foreach ($namespaceAliases as $alias => $fullyQualifiedClass) {
                $aliasesByLowercaseName[ strtolower($alias) ] = [ $alias, $fullyQualifiedClass ];
            }

            for ($index = $namespace->getScopeStartIndex(); $index <= $namespace->getScopeEndIndex(); $index++) {

                if (!$tokens[ $index ]->isGivenKind(T_STRING) || $this->isWithinRanges($index, $useRanges)) {
                    continue;
                }

                $alias = $aliasesByLowercaseName[ strtolower($tokens[ $index ]->getContent())] ?? null;

                if ($alias !== null) {
                    $usedAliases[ $namespaceIndex ][ $alias[ 0 ]] = $alias[ 1 ];
                }

            }

        }

        return $usedAliases;
    }

    /**
     * @return list<array{int, int}>
     */
    private function getUseRanges(Tokens $tokens, NamespaceAnalysis $namespace, NamespaceUsesAnalyzer $namespaceUsesAnalyzer): array
    {
        $useRanges = [];

        foreach ($namespaceUsesAnalyzer->getDeclarationsInNamespace($tokens, $namespace, true) as $use) {
            $useRanges[ $use->getStartIndex() ] = [ $use->getStartIndex(), $use->getEndIndex() ];
        }

        return array_values($useRanges);
    }

    /**
     * @param list<array{int, int}> $ranges
     */
    private function isWithinRanges(int $index, array $ranges): bool
    {
        foreach ($ranges as [ $startIndex, $endIndex ]) {

            if ($index >= $startIndex && $index <= $endIndex) {
                return true;
            }

        }

        return false;
    }
}

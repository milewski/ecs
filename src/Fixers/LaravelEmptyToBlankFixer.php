<?php

declare(strict_types = 1);

namespace Milewski\ECS\Fixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class LaravelEmptyToBlankFixer extends AbstractFixer
{
    /**
     * @var array<string, bool>
     */
    private array $laravelDirectoryCache = [];

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
        if ($this->isLaravelProject($file) === false) {
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

    private function isLaravelProject(SplFileInfo $file): bool
    {
        if ((function_exists('blank') && function_exists('filled'))
            || class_exists('Illuminate\Foundation\Application')) {
            return true;
        }

        $realPath = $file->getRealPath();
        $directory = $realPath === false ? $file->getPath() : dirname($realPath);
        $visitedDirectories = [];
        $isLaravelProject = false;

        while (true) {

            $visitedDirectories[] = $directory;

            if ($this->hasLaravelApplicationFiles($directory)) {

                $isLaravelProject = true;

                break;

            }

            if (array_key_exists($directory, $this->laravelDirectoryCache)) {

                $isLaravelProject = $this->laravelDirectoryCache[ $directory ];

                break;

            }

            $parentDirectory = dirname($directory);

            if ($parentDirectory === $directory) {
                break;
            }

            $directory = $parentDirectory;

        }

        foreach ($visitedDirectories as $visitedDirectory) {
            $this->laravelDirectoryCache[ $visitedDirectory ] = $isLaravelProject;
        }

        return $isLaravelProject;
    }

    private function hasLaravelApplicationFiles(string $directory): bool
    {
        return is_file(sprintf('%s/artisan', $directory))
            && is_file(sprintf('%s/bootstrap/app.php', $directory));
    }
}

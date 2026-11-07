<?php

declare(strict_types = 1);

namespace Milewski\ECS\Sniffs;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

final class ForbidErrorSuppressionSniff implements Sniff
{
    private const string ERROR_MESSAGE = 'Error suppression with @ is forbidden. Handle errors explicitly instead.';

    public function register(): array
    {
        return [ T_ASPERAND ];
    }

    public function process(File $phpcsFile, int $stackPtr): void
    {
        $phpcsFile->addError(
            error: self::ERROR_MESSAGE,
            stackPtr: $stackPtr,
            code: 'Found',
        );
    }
}

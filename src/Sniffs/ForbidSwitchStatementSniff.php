<?php

declare(strict_types = 1);

namespace Milewski\ECS\Sniffs;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

final class ForbidSwitchStatementSniff implements Sniff
{
    private const string ERROR_MESSAGE = 'Switch statements are forbidden. Use a match expression instead.';

    public function register(): array
    {
        return [ T_SWITCH ];
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

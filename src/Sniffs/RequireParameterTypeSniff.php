<?php

declare(strict_types = 1);

namespace Milewski\ECS\Sniffs;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

final class RequireParameterTypeSniff implements Sniff
{
    private const string ERROR_MESSAGE = 'Parameter "%s" is missing an explicit native type declaration. Inspect every caller and how the value is used, then declare the narrowest accurate type: prefer a specific class or interface, scalar, nullable, union, or intersection type. Do not add "mixed" merely to silence this error. Use "mixed" only when the API intentionally accepts values of any type and no narrower type or union can truthfully describe its contract.';

    public function register(): array
    {
        return [ T_FUNCTION, T_CLOSURE, T_FN ];
    }

    public function process(File $phpcsFile, int $stackPtr): void
    {
        foreach ($phpcsFile->getMethodParameters($stackPtr) as $parameter) {

            if ($parameter[ 'type_hint' ] !== '') {
                continue;
            }

            $phpcsFile->addError(
                error: self::ERROR_MESSAGE,
                stackPtr: $parameter[ 'token' ],
                code: 'Missing',
                data: [ $parameter[ 'name' ] ],
            );

        }
    }
}

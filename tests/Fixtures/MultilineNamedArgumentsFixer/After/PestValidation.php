<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MultilineNamedArgumentsFixer;

use Closure;

final class PestValidation
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
    }
}

test('test_analytics_metadata_rejects_excessive_nesting', function (): void {

    $value = 'safe';

    for ($depth = 0; $depth < 7; $depth++) {
        $value = [ 'level' => $value ];
    }

    $messages = [];

    new PestValidation()->validate(
        attribute: 'metadata',
        value: $value,
        fail: static function (string $message) use (&$messages): void {
            $messages[] = $message;
        },
    );

});

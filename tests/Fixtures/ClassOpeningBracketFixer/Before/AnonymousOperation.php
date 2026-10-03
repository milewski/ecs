<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\ClassOpeningBracketFixer;

final class AnonymousOperation
{
    public function operation(): Operation
    {
        return new class () extends Operation {
            public function __invoke(FormDefinitionImporter $forms, ProviderCommerceImporter $commerce): void
            {
                $forms->importDirectory(database_path('seeders/data/forms/definitions'));
                $commerce->importDirectory(database_path('seeders/data/forms/providers'));
            }
        };
    }
}

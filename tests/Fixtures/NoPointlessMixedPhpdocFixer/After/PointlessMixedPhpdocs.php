<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\NoPointlessMixedPhpdocFixer;

final class PointlessMixedPhpdocs
{
    public function acceptArray(array $data): void
    {
    }

    public function rows(): array
    {
        return [];
    }

    public function hydrate(array $data): void
    {
    }

    public function acceptMixed(mixed $value): void
    {
    }

    public function acceptMultilineArray(array $data): void
    {
    }

    public function uppercaseRows(): array
    {
        return [];
    }

    public function acceptDocumentedValues(array $values): void
    {
    }

    /**
     * @param array<string, string> $data
     *
     * @return list<string>
     */
    public function normalizedRows(array $data): array
    {
        return array_values($data);
    }
}

<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\NoPointlessMixedPhpdocFixer;

final class PointlessMixedPhpdocs
{
    /**
     * @param array<string, mixed> $data
     */
    public function acceptArray(array $data): void
    {
    }

    /**
     * @return list<mixed>
     */
    public function rows(): array
    {
        return [];
    }

    /**
     * Hydrates a record from external data.
     *
     * @param array<string, mixed> $data
     */
    public function hydrate(array $data): void
    {
    }

    /** @param mixed $value */
    public function acceptMixed(mixed $value): void
    {
    }

    /**
     * @param array<
     *     string,
     *     mixed
     * > $data
     */
    public function acceptMultilineArray(array $data): void
    {
    }

    /**
     * @RETURN LIST<MIXED>
     */
    public function uppercaseRows(): array
    {
        return [];
    }

    /**
     * @param list<mixed> $values Values are ordered by priority.
     */
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

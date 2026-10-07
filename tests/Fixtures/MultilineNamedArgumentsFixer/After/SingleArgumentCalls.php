<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MultilineNamedArgumentsFixer;

use LogicException;

function collect(array $values): Collection
{
    return new Collection();
}

final class Collection
{
    public function map(callable $callback): self
    {
        return $this;
    }

    public function values(): self
    {
        return $this;
    }

    public function all(): array
    {
        return [];
    }
}

final class SingleArgumentCalls
{
    private Collection $values;

    public function values(): array
    {
        return $this->values
            ->map(static fn (mixed $value): mixed => $value instanceof Collection ? $value->values()->all() : $value)
            ->all();
    }

    public function options(array $definition): QuestionData
    {
        return new QuestionData(
            options: collect($definition[ 'options' ])->map(
                callback: static fn (array $option): FormQuestionOptionData => FormQuestionOptionData::fromDefinition($option),
            ),
        );
    }

    public function validate(mixed $metadata, string $answerKey): void
    {
        if (!$metadata instanceof FormFieldMetadataData) {

            throw new LogicException(
                sprintf('Validated answer %s has no field metadata.', $answerKey),
            );

        }
    }
}

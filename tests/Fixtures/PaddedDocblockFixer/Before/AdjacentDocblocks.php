<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\PaddedDocblockFixer;

final class AdjacentDocblocks
{
    /**
     * @var list<string>
     */
    private array $keys = [];

    public function reconcile(object $existingByKey, object $definition): void
    {
        /**
         * @var Experiment|null $existing
         */
        $existing = $existingByKey->get($definition->key);
        /**
         * @var Experiment|null $existingForDecisionContext
         */
        $existingForDecisionContext = $existingByKey->first(
            static fn (Experiment $candidate): bool => $candidate->decision_point === $definition->decisionPoint
                && $candidate->context_key === $definition->contextKey,
        );

        /**
         * @var Experiment|null $existing
         */
        $existing = $existingForDecisionContext;
        /**
         * @var Experiment|null $existing
         */
        $existing = $existingByKey->get($definition->key);
    }
}

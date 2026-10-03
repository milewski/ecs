<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MethodChainFixer;

final class CustomerNotePageData
{
    public function __construct(
        public readonly array $notes,
        public readonly bool $hasMore,
    )
    {
    }
}

final class CustomerNotePages
{
    public function paginate(object $notes): CustomerNotePageData
    {
        return new CustomerNotePageData($notes->take(CrmPageData::SIZE)->map(fn (CustomerNote $note): CustomerNoteData => $this->presentNote($note))->all(), $notes->count() > CrmPageData::SIZE);
    }
}

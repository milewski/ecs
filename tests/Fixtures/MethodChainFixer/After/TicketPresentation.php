<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MethodChainFixer;

final class TicketMessageData
{
    public function __construct(
        public readonly string $id,
        public readonly string $kind,
        public readonly string $author,
        public readonly string $time,
        public readonly string $text,
    )
    {
    }
}

final class TicketPresentation
{
    private function present(Ticket $ticket): TicketData
    {
        return new TicketData(
            id: $ticket->id, customerId: $ticket->lead_id,
            from: $this->leadRepository->find($ticket->lead_id)?->email,
            subject: $ticket->subject, category: $ticket->category, priority: $ticket->priority, status: $ticket->status,
            assignee: $ticket->assignee_name,
            messages: $this->ticketMessageRepository
                ->forTicket($ticket->id)
                ->map(static fn (TicketMessage $message): TicketMessageData => new TicketMessageData(
                    id: (string) $message->id,
                    kind: $message->kind,
                    author: $message->author_name,
                    time: $message->created_at->toISOString(),
                    text: $message->text,
                ))
                ->all(),
            updatedAt: $ticket->updated_at->toISOString(), archived: $ticket->isArchived(),
        );
    }
}

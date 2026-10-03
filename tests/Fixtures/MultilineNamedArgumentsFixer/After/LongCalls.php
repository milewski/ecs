<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MultilineNamedArgumentsFixer;

final class TicketPageData
{
    public function __construct(
        public readonly array $tickets,
        public readonly int $page,
        public readonly bool $hasMore,
    )
    {
    }
}

final class LongCalls
{
    public function paginate(object $visible, object $page, object $tickets): TicketPageData
    {
        return new TicketPageData(
            tickets: $visible->map(fn (Ticket $ticket): TicketData => $this->present($ticket))->all(),
            page: $page->page,
            hasMore: $tickets->count() > $visible->count(),
        );
    }

    public function named(object $visible, object $page, object $tickets): TicketPageData
    {
        return new TicketPageData(
            tickets: $visible->map(fn (Ticket $ticket): TicketData => $this->present($ticket))->all(),
            page: $page->page,
            hasMore: $tickets->count() > $visible->count(),
        );
    }

    public function nested(object $visible, object $page, object $tickets): TicketPageData
    {
        return $this->withLabel(
            data: new TicketPageData(
                tickets: $visible->map(fn (Ticket $ticket): TicketData => $this->present($ticket))->all(),
                page: $page->page,
                hasMore: $tickets->count() > $visible->count(),
            ),
            label: 'Tickets',
        );
    }

    private function withLabel(TicketPageData $data, string $label): TicketPageData
    {
        return $data;
    }
}

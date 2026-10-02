<?php

declare(strict_types = 1);

namespace DigitalCreative\ECS\Tests\Fixtures\FunctionParameterLayout;

final class TicketOperations
{
    public function link(Ticket $ticket, int $leadId): Ticket
    {
        $ticket->lead_id = $leadId;
        $ticket->save();

        return $ticket;
    }

    public function touch(Ticket $ticket): void
    {
        $ticket->touch();
    }
}

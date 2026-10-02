<?php

declare(strict_types = 1);

interface TicketWriter
{
    public function touch(Ticket $ticket): void;
}

abstract class AbstractTicketWriter implements TicketWriter
{
    abstract public function touch(Ticket $ticket): void;

    public function reset(): void
    {
    }

    public function save(Ticket $ticket): void
    {
        $ticket->save();
    }

    protected function &tickets(): array
    {
        return $this->tickets;
    }

    protected function document(): void
    {
        /* Keep this comment. */
    }

    protected function start(Ticket $ticket): void
    {
        $ticket->touch();
    }

    protected function finish(Ticket $ticket): void
    {
        $ticket->touch();
    }
}

function touch_ticket(Ticket $ticket): void
{
    $ticket->touch();
}

$callback = function (Ticket $ticket): void { $ticket->touch(); };

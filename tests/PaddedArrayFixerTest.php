<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests;

use Milewski\ECS\Tests\Support\EcsTestCase;

final class PaddedArrayFixerTest extends EcsTestCase
{
    public function test_long_model_casts_expand_with_the_default_preset(): void
    {
        $input = <<<'PHP'
        <?php

        declare(strict_types = 1);

        use Illuminate\Database\Eloquent\Model;

        final class Ticket extends Model
        {
            protected function casts(): array
            {
                return [ 'status' => TicketStatus::class, 'category' => TicketCategory::class, 'priority' => TicketPriority::class, 'response_due_at' => 'immutable_datetime', 'resolution_due_at' => 'immutable_datetime', 'first_replied_at' => 'immutable_datetime', 'last_mail_at' => 'immutable_datetime' ];
            }
        }

        PHP;

        $expected = <<<'PHP'
        <?php

        declare(strict_types = 1);

        use Illuminate\Database\Eloquent\Model;

        final class Ticket extends Model
        {
            protected function casts(): array
            {
                return [
                    'status' => TicketStatus::class,
                    'category' => TicketCategory::class,
                    'priority' => TicketPriority::class,
                    'response_due_at' => 'immutable_datetime',
                    'resolution_due_at' => 'immutable_datetime',
                    'first_replied_at' => 'immutable_datetime',
                    'last_mail_at' => 'immutable_datetime',
                ];
            }
        }

        PHP;

        $this->assertCodeIsFixedTo($input, $expected, 'Ticket.php');
        $this->assertCodeIsFixedTo($expected, $expected, 'Ticket.php');
    }
}

<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests;

use Milewski\ECS\Tests\Support\EcsTestCase;

final class PaddedArrayFixerTest extends EcsTestCase
{
    public function test_match_scenario_arrays_preserve_their_existing_rows_with_the_default_preset(): void
    {
        $input = <<<'PHP'
        <?php

        declare(strict_types = 1);

        use RuntimeException;

        final class ValidationScenarios
        {
            private function patternScenarios(FormValidationRuleData $rule): array
            {
                return match ($rule->validationPattern()) {
                    FormValidationPattern::UsState => [
                        [ 'name' => 'valid', 'value' => 'California', 'answers' => [], 'today' => self::TODAY ],
                        [ 'name' => 'invalid', 'value' => 'Atlantis', 'answers' => [], 'today' => self::TODAY ],
                    ],
                    null => throw new RuntimeException('Pattern validation rule has no pattern.'),
                };
            }
        }

        PHP;

        $expected = str_replace(
            "null => throw new RuntimeException('Pattern validation rule has no pattern.'),",
            "null => throw new RuntimeException(\n                'Pattern validation rule has no pattern.',\n            ),",
            $input,
        );

        $this->assertCodeIsFixedTo($input, $expected, 'ValidationScenarios.php');
        $this->assertCodeIsFixedTo($expected, $expected, 'ValidationScenarios.php');

        $flattened = str_replace(
            "FormValidationPattern::UsState => [\n                [ 'name' => 'valid', 'value' => 'California', 'answers' => [], 'today' => self::TODAY ],\n                [ 'name' => 'invalid', 'value' => 'Atlantis', 'answers' => [], 'today' => self::TODAY ],\n            ],",
            "FormValidationPattern::UsState => [ [ 'name' => 'valid', 'value' => 'California', 'answers' => [], 'today' => self::TODAY ], [ 'name' => 'invalid', 'value' => 'Atlantis', 'answers' => [], 'today' => self::TODAY ] ],",
            $input,
        );

        $this->assertNotSame($input, $flattened);
        $this->assertCodeIsFixedTo($flattened, $expected, 'ValidationScenarios.php');
    }

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

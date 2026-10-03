<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\PaddedMultilineStatementFixer;

final class MultilineStatements
{
    public function execute(): void
    {
        $customer2 = $this->createCustomer();
        $data = [
            1,
            2,
            3,
        ];

        $this->actingAs($customer1)
            ->postJson(route('events.bookings.create', $event->id), [
                'selected_location' => 'Glenfiddich Shanghai',
            ]);

        $this->actingAs($customer2)
            ->postJson(route('events.bookings.create', $event->id), [
                'selected_location' => 'Glenfiddich Shanghai',
            ]);

        $response = $this->actingAs($customer1)->getJson(route('events.bookings.index'));
        $data1 = [
            1,
            2,
            3,
        ];

        $data2 = [
            1,
            2,
            3,
        ];

        $singleLine = true;
        $data3 = [
            1,
            2,
            3,
        ];
    }
}

<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\StatementGroupingFixer;

final class OrderWorkflow
{
    public function execute(): void
    {
        $order = [ $thirdCollection->id, $firstCollection->id, $secondCollection->id ];
        Livewire::actingAs($user)->test(ListCollections::class)->call('reorderTable', $order)->assertOk();
        Livewire::actingAs($user)->test(ListCollections::class)->call('reorderTable', $order)->assertOk();
        $this->assertDatabaseHas('collections', [ 'id' => $thirdCollection->id, 'sort_order' => 1 ]);
        $this->assertDatabaseHas('collections', [ 'id' => $firstCollection->id, 'sort_order' => 2 ]);
        $this->assertDatabaseHas('collections', [ 'id' => $secondCollection->id, 'sort_order' => 3 ]);
    }
}

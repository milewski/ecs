<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\StatementGroupingFixer;

final class CampaignWorkflow
{
    public function updatesSharing(): void
    {
        Storage::fake();
        Queue::fake();
        $campaign = CampaignFactory::new()->create();
        Storage::disk()->put('campaigns/sharing/poster.jpg', 'image');
        $sharing = json_decode($campaign->getRawOriginal('sharing'), true);
        $sharing[ 'description' ] = 'Updated sharing description';
        $campaign->setAttribute('sharing', $sharing);
    }

    public function savesCampaign(): void
    {
        Storage::fake();
        Queue::fake();
        $campaign = CampaignFactory::new()->create();
        Storage::disk()->put('campaigns/sharing/poster.jpg', 'image');
        $campaign->name = 'Updated campaign name';

        $campaign->save();

        Queue::assertNothingPushed();
    }
}

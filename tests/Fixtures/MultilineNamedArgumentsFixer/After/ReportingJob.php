<?php

declare(strict_types = 1);

final class ReportingJob
{
    public function middleware(): array
    {
        // ponytail: serialize reporting per credential; add a measured token bucket if throughput becomes a bottleneck.
        return [
            new WithoutOverlapping(sprintf('reporting-google-ads:%s', hash('sha256', (string) config('google-ads.developer_token'))))
                ->shared()
                ->releaseAfter(60)
                ->expireAfter(60),
            app(ThrottleGoogleAdsQuota::class),
        ];
    }
}

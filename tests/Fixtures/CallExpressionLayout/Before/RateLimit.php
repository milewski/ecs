<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\CallExpressionLayout;

RateLimiter::for(
    name: 'crm-search',
    callback: static fn (Request $request): Limit => Limit::perMinute(30)->by(sprintf(
        'crm-search:%s',
        $request->user('crm')?->getAuthIdentifier(),
    )),
);

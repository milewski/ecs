<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MultilineNamedArgumentsFixer;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Milewski\ECS\Tests\Support\ReflectionRateLimit as Limit;

RateLimiter::for(
    name: 'crm-search',
    callback: static fn (Request $request): Limit => Limit::perMinute(30)->by(
        key: sprintf('crm-search:%s', $request->user('crm')?->getAuthIdentifier()),
    ),
);

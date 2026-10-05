<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\MethodChainFixer;

final class RecoveryRequests
{
    public function verify(string $url, string $replacement, string $plaintext): void
    {
        $this->assertNotSame($url, $replacement);
        $this->getJson(route('recovery.show', [ 'token' => $plaintext ]))
            ->assertExactJson([ 'status' => 'unavailable' ]);

        $this->getJson(route('recovery.show', [ 'token' => Str::of($replacement)->after('/restart/')->value() ]))
            ->assertJsonPath('status', 'active');
    }
}

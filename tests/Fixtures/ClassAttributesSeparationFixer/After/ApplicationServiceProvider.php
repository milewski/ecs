<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\ClassAttributesSeparationFixer;

final class ApplicationServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FilamentResetPassword::class, ResetPassword::class);
    }

    public function boot(): void
    {
    }

    public function shutdown(): void
    {
    }
}

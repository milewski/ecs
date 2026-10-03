<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\TraitUseSpacingFixer;

final class SingleTraitController
{
    use ChecksExistingAppointments;

    public function __invoke(BookAppointmentRequest $request): ApiResponse
    {
        return $this->response();
    }
}

<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\TraitUseSpacingFixer;

final class MultipleTraitsController
{
    use AnotherTrait;
    use ChecksExistingAppointments;

    public function __invoke(BookAppointmentRequest $request): ApiResponse
    {
        return $this->response();
    }
}

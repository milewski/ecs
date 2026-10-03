<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Support;

final class ReflectionConditionalModel
{
    /**
     * @return ReflectionConditionalQuery<static>
     */
    public static function query()
    {
        return new ReflectionConditionalQuery();
    }
}

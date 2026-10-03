<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\StatementGroupingFixer;

class AssignmentAndProperties
{
    protected static ?int $sort = -1;
    protected static bool $isLazy = false;
    protected string $view = 'filament.widgets.deployment-info-widget';
    protected string $label = 'Deployment information';
    protected int|string|array $columnSpan = 'full';
    protected bool|int $polling = false;
    protected int $columns = 1;

    public function parsesSlots(string $timeSlot): void
    {
        [ $slotStartTime, $slotEndTime ] = explode('-', $timeSlot);
        $slotStart = Carbon::createFromFormat('H:i', $slotStartTime);
        $slotEnd = Carbon::createFromFormat('H:i', $slotEndTime);
        [ $first, $second ] = explode('-', $timeSlot);
        [ $third, $fourth ] = explode('-', $timeSlot);
    }
}

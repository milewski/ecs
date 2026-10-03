<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Rules;

use Milewski\ECS\Tests\Support\EcsTestCase;

final class PaddedBlockFixerTest extends EcsTestCase
{
    public function provideConfig(): string
    {
        return __DIR__ . '/../Support/PaddedBlock.php';
    }

    public function test_adds_a_blank_line_after_completed_control_blocks(): void
    {
        $this->assertFixtureIsFixedTo(
            inputFixture: __DIR__ . '/../Fixtures/PaddedBlock/post_block_statement_unfixed.php',
            expectedFixture: __DIR__ . '/../Fixtures/PaddedBlock/post_block_statement_fixed.php',
        );
    }

    public function test_removes_trailing_padding_from_named_functions(): void
    {
        $this->assertFixtureIsFixedTo(
            inputFixture: __DIR__ . '/../Fixtures/PaddedBlock/named_function_padding_unfixed.php',
            expectedFixture: __DIR__ . '/../Fixtures/PaddedBlock/named_function_padding_fixed.php',
        );
    }

    public function test_does_not_separate_linked_control_blocks(): void
    {
        $this->assertFixturePasses(
            fixture: __DIR__ . '/../Fixtures/PaddedBlock/linked_blocks.php',
        );
    }
}

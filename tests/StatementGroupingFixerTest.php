<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests;

use Milewski\ECS\Tests\Support\EcsTestCase;

final class StatementGroupingFixerTest extends EcsTestCase
{
    public function test_groups_variables_static_calls_and_this_calls_separately(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/StatementGroupingFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/OrderWorkflow.php',
            expectedFixture: $fixtureDirectory . '/After/OrderWorkflow.php',
        );
    }

    public function test_groups_new_object_calls_inside_pest_closures(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/StatementGroupingFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/PestValidation.php',
            expectedFixture: $fixtureDirectory . '/After/PestValidation.php',
        );
    }

    public function test_groups_static_calls_and_matching_variable_receivers(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/StatementGroupingFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/CampaignWorkflow.php',
            expectedFixture: $fixtureDirectory . '/After/CampaignWorkflow.php',
        );
    }

    public function test_preserves_multiline_spacing_inside_matching_groups(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/StatementGroupingFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/MultilineWorkflow.php',
            expectedFixture: $fixtureDirectory . '/After/MultilineWorkflow.php',
        );
    }

    public function test_groups_destructuring_assignments_and_properties_by_type(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/StatementGroupingFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/AssignmentAndProperties.php',
            expectedFixture: $fixtureDirectory . '/After/AssignmentAndProperties.php',
        );
    }

    public function test_only_adds_group_separators_without_removing_existing_lines(): void
    {
        $fixtureDirectory = __DIR__ . '/Fixtures/StatementGroupingFixer';

        $this->assertFixtureIsFixedTo(
            inputFixture: $fixtureDirectory . '/Before/AdditiveGrouping.php',
            expectedFixture: $fixtureDirectory . '/After/AdditiveGrouping.php',
        );
    }

    public function test_does_not_group_anonymous_class_members_with_the_outer_statement(): void
    {
        $this->assertFixturePasses(
            fixture: __DIR__ . '/Fixtures/StatementGroupingFixer/AnonymousMigration.php',
        );
    }
}

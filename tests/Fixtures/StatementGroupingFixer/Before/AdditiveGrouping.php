<?php

declare(strict_types = 1);

namespace Milewski\ECS\Tests\Fixtures\StatementGroupingFixer;

final class AdditiveGrouping
{
    public function preservesExistingSeparator(): void
    {
        Schema::rename('visitor_events', 'web_events');

        Schema::table('web_events', function (Blueprint $table): void {

            $table->char('schema_version', 2)->default('v1');
            $table->dropColumn('updated_at');
            $table->index('created_at');

        });
    }

    public function addsMissingSeparator(string $legacyTable): void
    {
        $schema->rename('stripe_webhook_events', $legacyTable);
        $legacyTables[] = $legacyTable;
    }
}

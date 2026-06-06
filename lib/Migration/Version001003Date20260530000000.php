<?php

declare(strict_types=1);

namespace OCA\Git\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version001003Date20260530000000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('nextgit_webhooks')) {
            $table = $schema->createTable('nextgit_webhooks');
            $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
            $table->addColumn('repo_id', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
            $table->addColumn('url', Types::STRING, ['notnull' => true, 'length' => 512]);
            $table->addColumn('secret', Types::STRING, ['notnull' => true, 'length' => 255]);
            $table->addColumn('events', Types::TEXT, ['notnull' => true, 'default' => '[]']);
            $table->addColumn('active', Types::INTEGER, ['notnull' => true, 'default' => 1]);
            $table->addColumn('created_at', Types::BIGINT, ['notnull' => true, 'default' => 0]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['repo_id'], 'nextgit_webhooks_repo');
        }

        return $schema;
    }
}

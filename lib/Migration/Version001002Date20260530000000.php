<?php

declare(strict_types=1);

namespace OCA\Git\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version001002Date20260530000000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('nextgit_pulls')) {
            $table = $schema->createTable('nextgit_pulls');
            $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
            $table->addColumn('repo_id', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
            $table->addColumn('number', Types::INTEGER, ['notnull' => true, 'default' => 0]);
            $table->addColumn('title', Types::STRING, ['notnull' => true, 'length' => 255]);
            $table->addColumn('body', Types::TEXT, ['notnull' => false, 'default' => null]);
            $table->addColumn('state', Types::STRING, ['notnull' => true, 'length' => 16, 'default' => 'open']);
            $table->addColumn('creator_uid', Types::STRING, ['notnull' => true, 'length' => 64]);
            $table->addColumn('head_branch', Types::STRING, ['notnull' => true, 'length' => 255]);
            $table->addColumn('base_branch', Types::STRING, ['notnull' => true, 'length' => 255]);
            $table->addColumn('created_at', Types::BIGINT, ['notnull' => true, 'default' => 0]);
            $table->addColumn('updated_at', Types::BIGINT, ['notnull' => true, 'default' => 0]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['repo_id'], 'nextgit_pulls_repo');
            $table->addIndex(['repo_id', 'number'], 'nextgit_pulls_repo_num');
        }

        return $schema;
    }
}

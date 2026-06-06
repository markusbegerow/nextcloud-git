<?php

declare(strict_types=1);

namespace OCA\Git\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version001001Date20260530000000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        // Add assignee_uid column to issues if missing
        if ($schema->hasTable('nextgit_issues')) {
            $table = $schema->getTable('nextgit_issues');
            if (!$table->hasColumn('assignee_uid')) {
                $table->addColumn('assignee_uid', Types::STRING, ['notnull' => false, 'length' => 64, 'default' => null]);
            }
        }

        if (!$schema->hasTable('nextgit_labels')) {
            $table = $schema->createTable('nextgit_labels');
            $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
            $table->addColumn('repo_id', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
            $table->addColumn('name', Types::STRING, ['notnull' => true, 'length' => 50]);
            $table->addColumn('color', Types::STRING, ['notnull' => true, 'length' => 7, 'default' => '#0075ca']);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['repo_id'], 'nextgit_labels_repo');
        }

        if (!$schema->hasTable('nextgit_issue_labels')) {
            $table = $schema->createTable('nextgit_issue_labels');
            $table->addColumn('issue_id', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
            $table->addColumn('label_id', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
            $table->setPrimaryKey(['issue_id', 'label_id']);
        }

        return $schema;
    }
}

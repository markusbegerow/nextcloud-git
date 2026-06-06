<?php

declare(strict_types=1);

namespace OCA\Git\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class WebhookMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'nextgit_webhooks', Webhook::class);
    }

    public function deleteByRepo(int $repoId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete($this->getTableName())
           ->where($qb->expr()->eq('repo_id', $qb->createNamedParameter($repoId, IQueryBuilder::PARAM_INT)))
           ->executeStatement();
    }

    /** @return Webhook[] */
    public function findAllByRepo(int $repoId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from($this->getTableName())
           ->where($qb->expr()->eq('repo_id', $qb->createNamedParameter($repoId, IQueryBuilder::PARAM_INT)))
           ->orderBy('id', 'ASC');
        return $this->findEntities($qb);
    }
}

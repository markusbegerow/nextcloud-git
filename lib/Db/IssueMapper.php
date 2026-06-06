<?php

declare(strict_types=1);

namespace OCA\Git\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class IssueMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'nextgit_issues', Issue::class);
    }

    /** @return Issue[] */
    public function findAllByRepo(int $repoId, string $state = 'open'): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from($this->getTableName())
           ->where($qb->expr()->eq('repo_id', $qb->createNamedParameter($repoId, IQueryBuilder::PARAM_INT)));
        if ($state !== 'all') {
            $qb->andWhere($qb->expr()->eq('state', $qb->createNamedParameter($state)));
        }
        $qb->orderBy('number', 'DESC');
        return $this->findEntities($qb);
    }

    public function findByRepoAndNumber(int $repoId, int $number): Issue {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from($this->getTableName())
           ->where($qb->expr()->eq('repo_id', $qb->createNamedParameter($repoId, IQueryBuilder::PARAM_INT)))
           ->andWhere($qb->expr()->eq('number', $qb->createNamedParameter($number, IQueryBuilder::PARAM_INT)));
        return $this->findEntity($qb);
    }

    public function deleteByRepo(int $repoId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete($this->getTableName())
           ->where($qb->expr()->eq('repo_id', $qb->createNamedParameter($repoId, IQueryBuilder::PARAM_INT)))
           ->executeStatement();
    }

    public function getNextNumber(int $repoId): int {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->createFunction('COALESCE(MAX(number), 0) + 1'))
           ->from($this->getTableName())
           ->where($qb->expr()->eq('repo_id', $qb->createNamedParameter($repoId, IQueryBuilder::PARAM_INT)));
        $result = $qb->executeQuery();
        $val = (int) $result->fetchOne();
        $result->closeCursor();
        return max(1, $val);
    }
}

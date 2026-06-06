<?php

declare(strict_types=1);

namespace OCA\Git\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class RepoMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'nextgit_repos', Repo::class);
    }

    /** @return Repo[] */
    public function findAllByOwner(string $ownerUid): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from($this->getTableName())
           ->where($qb->expr()->eq('owner_uid', $qb->createNamedParameter($ownerUid)))
           ->orderBy('name', 'ASC');
        return $this->findEntities($qb);
    }

    public function findByOwnerAndName(string $ownerUid, string $name): Repo {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from($this->getTableName())
           ->where($qb->expr()->eq('owner_uid', $qb->createNamedParameter($ownerUid)))
           ->andWhere($qb->expr()->eq('name', $qb->createNamedParameter($name)));
        return $this->findEntity($qb);
    }

    public function existsByName(string $ownerUid, string $name): bool {
        try {
            $this->findByOwnerAndName($ownerUid, $name);
            return true;
        } catch (DoesNotExistException) {
            return false;
        }
    }
}

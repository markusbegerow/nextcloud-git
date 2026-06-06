<?php

declare(strict_types=1);

namespace OCA\Git\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method int getRepoId()
 * @method void setRepoId(int $id)
 * @method int getNumber()
 * @method void setNumber(int $n)
 * @method string getTitle()
 * @method void setTitle(string $t)
 * @method string|null getBody()
 * @method void setBody(?string $b)
 * @method string getState()
 * @method void setState(string $s)
 * @method string getCreatorUid()
 * @method void setCreatorUid(string $uid)
 * @method string|null getAssigneeUid()
 * @method void setAssigneeUid(?string $uid)
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $ts)
 * @method int getUpdatedAt()
 * @method void setUpdatedAt(int $ts)
 */
class Issue extends Entity {
    protected int $repoId = 0;
    protected int $number = 0;
    protected string $title = '';
    protected ?string $body = null;
    protected string $state = 'open';
    protected string $creatorUid = '';
    protected ?string $assigneeUid = null;
    protected int $createdAt = 0;
    protected int $updatedAt = 0;

    public function __construct() {
        $this->addType('repoId',    'integer');
        $this->addType('number',    'integer');
        $this->addType('createdAt', 'integer');
        $this->addType('updatedAt', 'integer');
    }

    public function toArray(): array {
        return [
            'id'           => $this->getId(),
            'repo_id'      => $this->repoId,
            'number'       => $this->number,
            'title'        => $this->title,
            'body'         => $this->body,
            'state'        => $this->state,
            'creator_uid'  => $this->creatorUid,
            'assignee_uid' => $this->assigneeUid,
            'created_at'   => $this->createdAt,
            'updated_at'   => $this->updatedAt,
        ];
    }
}

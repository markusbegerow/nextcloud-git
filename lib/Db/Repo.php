<?php

declare(strict_types=1);

namespace OCA\Git\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getOwnerUid()
 * @method void setOwnerUid(string $uid)
 * @method string getName()
 * @method void setName(string $name)
 * @method string|null getDescription()
 * @method void setDescription(?string $description)
 * @method int getIsPrivate()
 * @method void setIsPrivate(int $isPrivate)
 * @method string getDefaultBranch()
 * @method void setDefaultBranch(string $branch)
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $ts)
 * @method int getUpdatedAt()
 * @method void setUpdatedAt(int $ts)
 */
class Repo extends Entity {
    protected string $ownerUid = '';
    protected string $name = '';
    protected ?string $description = null;
    protected int $isPrivate = 0;
    protected string $defaultBranch = 'main';
    protected int $createdAt = 0;
    protected int $updatedAt = 0;

    public function __construct() {
        $this->addType('isPrivate', 'integer');
        $this->addType('createdAt', 'integer');
        $this->addType('updatedAt', 'integer');
    }

    public function toArray(): array {
        return [
            'id'             => $this->getId(),
            'owner_uid'      => $this->ownerUid,
            'name'           => $this->name,
            'description'    => $this->description,
            'is_private'     => (bool) $this->isPrivate,
            'default_branch' => $this->defaultBranch,
            'created_at'     => $this->createdAt,
            'updated_at'     => $this->updatedAt,
        ];
    }
}

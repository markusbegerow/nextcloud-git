<?php

declare(strict_types=1);

namespace OCA\Git\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method int getRepoId()
 * @method void setRepoId(int $id)
 * @method string getUrl()
 * @method void setUrl(string $url)
 * @method string getSecret()
 * @method void setSecret(string $s)
 * @method string getEvents()
 * @method void setEvents(string $e)
 * @method int getActive()
 * @method void setActive(int $a)
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $ts)
 */
class Webhook extends Entity {
    protected int $repoId = 0;
    protected string $url = '';
    protected string $secret = '';
    protected string $events = '[]';
    protected int $active = 1;
    protected int $createdAt = 0;

    public function __construct() {
        $this->addType('repoId',    'integer');
        $this->addType('active',    'integer');
        $this->addType('createdAt', 'integer');
    }

    public function toArray(): array {
        return [
            'id'         => $this->getId(),
            'repo_id'    => $this->repoId,
            'url'        => $this->url,
            'events'     => json_decode($this->events, true) ?? [],
            'active'     => (bool) $this->active,
            'created_at' => $this->createdAt,
        ];
    }
}

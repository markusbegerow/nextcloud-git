<?php

declare(strict_types=1);

namespace OCA\Git\Service;

use OCA\Git\Db\Issue;
use OCA\Git\Db\IssueMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IUserSession;
use RuntimeException;

class IssueService {
    public function __construct(
        private IssueMapper  $issueMapper,
        private RepoService  $repoService,
        private IUserSession $userSession,
    ) {}

    private function currentUid(): string {
        $user = $this->userSession->getUser();
        if ($user === null) throw new RuntimeException('Not logged in');
        return $user->getUID();
    }

    public function listIssues(string $owner, string $repoName, string $state = 'open'): array {
        $repo = $this->repoService->getRepo($owner, $repoName);
        if (!in_array($state, ['open', 'closed', 'all'], true)) {
            $state = 'open';
        }
        $issues = $this->issueMapper->findAllByRepo((int) $repo['id'], $state);
        return array_map(fn(Issue $i) => $i->toArray(), $issues);
    }

    public function createIssue(string $owner, string $repoName, string $title, string $body): array {
        $repo = $this->repoService->getRepo($owner, $repoName);
        $uid  = $this->currentUid();
        $now  = time();
        $num  = $this->issueMapper->getNextNumber((int) $repo['id']);

        $issue = new Issue();
        $issue->setRepoId((int) $repo['id']);
        $issue->setNumber($num);
        $issue->setTitle($title);
        $issue->setBody($body !== '' ? $body : null);
        $issue->setState('open');
        $issue->setCreatorUid($uid);
        $issue->setCreatedAt($now);
        $issue->setUpdatedAt($now);

        return $this->issueMapper->insert($issue)->toArray();
    }

    public function getIssue(string $owner, string $repoName, int $number): array {
        $repo = $this->repoService->getRepo($owner, $repoName);
        try {
            return $this->issueMapper->findByRepoAndNumber((int) $repo['id'], $number)->toArray();
        } catch (DoesNotExistException) {
            throw new RuntimeException('Issue not found.');
        }
    }

    public function updateIssue(string $owner, string $repoName, int $number, array $fields): array {
        $repo = $this->repoService->getRepo($owner, $repoName);
        try {
            $issue = $this->issueMapper->findByRepoAndNumber((int) $repo['id'], $number);
        } catch (DoesNotExistException) {
            throw new RuntimeException('Issue not found.');
        }

        if (isset($fields['title'])) {
            $issue->setTitle((string) $fields['title']);
        }
        if (isset($fields['body'])) {
            $issue->setBody((string) $fields['body']);
        }
        if (isset($fields['state']) && in_array($fields['state'], ['open', 'closed'], true)) {
            $issue->setState($fields['state']);
        }
        if (isset($fields['assignee_uid'])) {
            $issue->setAssigneeUid($fields['assignee_uid'] !== '' ? (string) $fields['assignee_uid'] : null);
        }
        $issue->setUpdatedAt(time());
        return $this->issueMapper->update($issue)->toArray();
    }
}

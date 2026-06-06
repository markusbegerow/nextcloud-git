<?php

declare(strict_types=1);

namespace OCA\Git\Service;

use OCA\Git\Db\IssueMapper;
use OCA\Git\Db\PullMapper;
use OCA\Git\Db\Repo;
use OCA\Git\Db\RepoMapper;
use OCA\Git\Db\WebhookMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IUserManager;
use OCP\IUserSession;
use RuntimeException;

class RepoService {
    public function __construct(
        private RepoMapper   $repoMapper,
        private GitService   $gitService,
        private IUserSession $userSession,
        private IUserManager $userManager,
        private IssueMapper  $issueMapper,
        private PullMapper   $pullMapper,
        private WebhookMapper $webhookMapper,
    ) {}

    private function currentUid(): string {
        $user = $this->userSession->getUser();
        if ($user === null) {
            throw new RuntimeException('Not logged in');
        }
        return $user->getUID();
    }

    /** @return array[] */
    public function listRepos(): array {
        $uid   = $this->currentUid();
        $repos = $this->repoMapper->findAllByOwner($uid);
        return array_map(fn(Repo $r) => $r->toArray(), $repos);
    }

    public function createRepo(string $name, string $description, bool $isPrivate): array {
        $uid = $this->currentUid();

        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{0,99}$/', $name)) {
            throw new RuntimeException('Invalid repository name. Use letters, numbers, hyphens, underscores, dots.');
        }
        if ($this->repoMapper->existsByName($uid, $name)) {
            throw new RuntimeException('A repository with that name already exists.');
        }

        $now  = time();
        $repo = new Repo();
        $repo->setOwnerUid($uid);
        $repo->setName($name);
        $repo->setDescription($description !== '' ? $description : null);
        $repo->setIsPrivate($isPrivate ? 1 : 0);
        $repo->setDefaultBranch('main');
        $repo->setCreatedAt($now);
        $repo->setUpdatedAt($now);

        $repo = $this->repoMapper->insert($repo);
        $this->gitService->initRepo($uid, $name);

        return $repo->toArray();
    }

    public function deleteRepo(string $name): void {
        $uid = $this->currentUid();
        try {
            $repo = $this->repoMapper->findByOwnerAndName($uid, $name);
        } catch (DoesNotExistException) {
            throw new RuntimeException('Repository not found.');
        }
        $repoId = (int) $repo->getId();
        $this->repoMapper->delete($repo);

        // Cascade: orphaned child records
        $this->issueMapper->deleteByRepo($repoId);
        $this->pullMapper->deleteByRepo($repoId);
        $this->webhookMapper->deleteByRepo($repoId);

        $this->gitService->deleteRepo($uid, $name);
    }

    public function getRepo(string $owner, string $name): array {
        try {
            $repo = $this->repoMapper->findByOwnerAndName($owner, $name);
        } catch (DoesNotExistException) {
            throw new RuntimeException('Repository not found.');
        }
        return $repo->toArray();
    }

    public function getBranches(string $owner, string $name): array {
        $this->getRepo($owner, $name);
        return $this->gitService->getBranches($owner, $name);
    }

    public function getCommits(string $owner, string $name, string $branch): array {
        $this->getRepo($owner, $name);
        return $this->gitService->getCommits($owner, $name, $branch);
    }

    public function getTree(string $owner, string $name, string $branch, string $path = ''): array {
        $this->getRepo($owner, $name);
        return $this->gitService->getTree($owner, $name, $branch, $path);
    }

    public function getBlob(string $owner, string $name, string $branch, string $path): string {
        $this->getRepo($owner, $name);
        return $this->gitService->getBlob($owner, $name, $branch, $path);
    }

    public function getReadme(string $owner, string $name, string $branch): ?string {
        $this->getRepo($owner, $name);
        return $this->gitService->getReadme($owner, $name, $branch);
    }

    public function getGraph(string $owner, string $name): array {
        $this->getRepo($owner, $name);
        return $this->gitService->getGraph($owner, $name);
    }

    public function uploadFiles(
        string $owner,
        string $name,
        string $branch,
        string $directory,
        array  $files,
        string $message
    ): void {
        $repo    = $this->getRepo($owner, $name);
        $branch  = $branch  !== '' ? $branch  : ($repo['default_branch'] ?? 'main');
        $message = $message !== '' ? $message : 'Upload via NextGit';
        $uid     = $this->currentUid();

        $this->gitService->commitFiles($owner, $name, $branch, $directory, $files, $message, $uid);
    }

    public function isEmpty(string $owner, string $name): bool {
        return $this->gitService->isEmpty($owner, $name);
    }

    public function updateRepo(string $name, array $fields): array {
        $uid = $this->currentUid();
        try {
            $repo = $this->repoMapper->findByOwnerAndName($uid, $name);
        } catch (DoesNotExistException) {
            throw new RuntimeException('Repository not found.');
        }

        if (isset($fields['description'])) {
            $repo->setDescription($fields['description'] !== '' ? (string) $fields['description'] : null);
        }
        if (isset($fields['default_branch'])) {
            $repo->setDefaultBranch((string) $fields['default_branch']);
        }
        if (isset($fields['name']) && (string) $fields['name'] !== $name) {
            $newName = (string) $fields['name'];
            if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{0,99}$/', $newName)) {
                throw new RuntimeException('Invalid repository name.');
            }
            if ($this->repoMapper->existsByName($uid, $newName)) {
                throw new RuntimeException('A repository with that name already exists.');
            }
            // Rename git directory first, then update DB
            $oldPath = $this->gitService->repoPath($uid, $name);
            $newPath = $this->gitService->repoPath($uid, $newName);
            if (!rename($oldPath, $newPath)) {
                throw new RuntimeException('Failed to rename repository directory.');
            }
            $repo->setName($newName);
        }

        $repo->setUpdatedAt(time());
        return $this->repoMapper->update($repo)->toArray();
    }

    public function transferRepo(string $name, string $newOwnerUid): array {
        $uid = $this->currentUid();
        if (!$this->userManager->userExists($newOwnerUid)) {
            throw new RuntimeException("User '$newOwnerUid' not found.");
        }
        try {
            $repo = $this->repoMapper->findByOwnerAndName($uid, $name);
        } catch (DoesNotExistException) {
            throw new RuntimeException('Repository not found.');
        }
        if ($this->repoMapper->existsByName($newOwnerUid, $name)) {
            throw new RuntimeException("Target user already has a repository named '$name'.");
        }

        $oldPath = $this->gitService->repoPath($uid, $name);
        $newPath = $this->gitService->repoPath($newOwnerUid, $name);
        $newDir  = dirname($newPath);
        if (!is_dir($newDir)) mkdir($newDir, 0750, true);

        if (!rename($oldPath, $newPath)) {
            throw new RuntimeException('Failed to move repository directory.');
        }

        $repo->setOwnerUid($newOwnerUid);
        $repo->setUpdatedAt(time());
        return $this->repoMapper->update($repo)->toArray();
    }
}

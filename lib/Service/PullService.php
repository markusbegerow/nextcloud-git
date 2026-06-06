<?php

declare(strict_types=1);

namespace OCA\Git\Service;

use OCA\Git\Db\Pull;
use OCA\Git\Db\PullMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IUserSession;
use RuntimeException;

class PullService {
    public function __construct(
        private PullMapper   $pullMapper,
        private RepoService  $repoService,
        private GitService   $gitService,
        private DiffService  $diffService,
        private IUserSession $userSession,
    ) {}

    private function currentUid(): string {
        $user = $this->userSession->getUser();
        if ($user === null) throw new RuntimeException('Not logged in');
        return $user->getUID();
    }

    public function listPulls(string $owner, string $repoName, string $state = 'open'): array {
        $repo = $this->repoService->getRepo($owner, $repoName);
        if (!in_array($state, ['open', 'merged', 'closed', 'all'], true)) $state = 'open';
        $pulls = $this->pullMapper->findAllByRepo((int) $repo['id'], $state);
        return array_map(fn(Pull $p) => $p->toArray(), $pulls);
    }

    public function createPull(string $owner, string $repoName, string $title, string $body, string $headBranch, string $baseBranch): array {
        $repo = $this->repoService->getRepo($owner, $repoName);
        $uid  = $this->currentUid();

        $branches = $this->gitService->getBranches($owner, $repoName);
        if (!in_array($headBranch, $branches, true)) throw new RuntimeException("Branch '$headBranch' not found.");
        if (!in_array($baseBranch, $branches, true)) throw new RuntimeException("Branch '$baseBranch' not found.");
        if ($headBranch === $baseBranch) throw new RuntimeException('Head and base branches must be different.');

        $now  = time();
        $num  = $this->pullMapper->getNextNumber((int) $repo['id']);

        $pull = new Pull();
        $pull->setRepoId((int) $repo['id']);
        $pull->setNumber($num);
        $pull->setTitle($title);
        $pull->setBody($body !== '' ? $body : null);
        $pull->setState('open');
        $pull->setCreatorUid($uid);
        $pull->setHeadBranch($headBranch);
        $pull->setBaseBranch($baseBranch);
        $pull->setCreatedAt($now);
        $pull->setUpdatedAt($now);

        return $this->pullMapper->insert($pull)->toArray();
    }

    public function getPull(string $owner, string $repoName, int $number): array {
        $repo = $this->repoService->getRepo($owner, $repoName);
        try {
            $pull = $this->pullMapper->findByRepoAndNumber((int) $repo['id'], $number);
        } catch (DoesNotExistException) {
            throw new RuntimeException('Pull request not found.');
        }
        $data = $pull->toArray();
        $data['diff'] = $this->diffService->getDiff($owner, $repoName, $pull->getBaseBranch(), $pull->getHeadBranch());
        return $data;
    }

    public function mergePull(string $owner, string $repoName, int $number): array {
        $repo = $this->repoService->getRepo($owner, $repoName);
        try {
            $pull = $this->pullMapper->findByRepoAndNumber((int) $repo['id'], $number);
        } catch (DoesNotExistException) {
            throw new RuntimeException('Pull request not found.');
        }
        if ($pull->getState() !== 'open') {
            throw new RuntimeException('Pull request is not open.');
        }

        $path = $this->gitService->repoPath($owner, $repoName);
        $base = escapeshellarg($pull->getBaseBranch());
        $head = escapeshellarg($pull->getHeadBranch());

        // Merge in the bare repo using git merge
        $cmd = "git checkout $base && git merge --no-ff $head -m " .
               escapeshellarg('Merge ' . $pull->getHeadBranch() . ' into ' . $pull->getBaseBranch() . ' (#' . $number . ')');

        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $env  = ['HOME' => '/root', 'PATH' => '/usr/bin:/bin', 'GIT_DIR' => $path];
        $proc = proc_open($cmd, $descriptors, $pipes, $path, $env);
        if (!is_resource($proc)) throw new RuntimeException('git merge failed');
        fclose($pipes[1]); fclose($pipes[2]);
        $code = proc_close($proc);
        if ($code !== 0) throw new RuntimeException('Merge conflict detected. Resolve conflicts before merging.');

        $pull->setState('merged');
        $pull->setUpdatedAt(time());
        return $this->pullMapper->update($pull)->toArray();
    }

    public function closePull(string $owner, string $repoName, int $number): array {
        $repo = $this->repoService->getRepo($owner, $repoName);
        try {
            $pull = $this->pullMapper->findByRepoAndNumber((int) $repo['id'], $number);
        } catch (DoesNotExistException) {
            throw new RuntimeException('Pull request not found.');
        }
        $pull->setState('closed');
        $pull->setUpdatedAt(time());
        return $this->pullMapper->update($pull)->toArray();
    }
}

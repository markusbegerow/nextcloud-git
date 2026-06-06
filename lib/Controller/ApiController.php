<?php

declare(strict_types=1);

namespace OCA\Git\Controller;

use OCA\Git\Service\RepoService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use RuntimeException;

class ApiController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private RepoService $repoService,
    ) {
        parent::__construct($appName, $request);
    }

    #[NoAdminRequired]
    public function listRepos(): DataResponse {
        return new DataResponse($this->repoService->listRepos());
    }

    #[NoAdminRequired]
    public function createRepo(): DataResponse {
        $params      = $this->request->getParams();
        $name        = trim((string) ($params['name'] ?? ''));
        $description = trim((string) ($params['description'] ?? ''));
        $isPrivate   = (bool) ($params['is_private'] ?? false);

        try {
            $repo = $this->repoService->createRepo($name, $description, $isPrivate);
            return new DataResponse($repo, 201);
        } catch (RuntimeException $e) {
            return new DataResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function deleteRepo(string $owner, string $name): DataResponse {
        try {
            $this->repoService->deleteRepo($name);
            return new DataResponse(['ok' => true]);
        } catch (RuntimeException $e) {
            return new DataResponse(['error' => $e->getMessage()], 404);
        }
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getBranches(string $owner, string $name): DataResponse {
        try {
            return new DataResponse($this->repoService->getBranches($owner, $name));
        } catch (RuntimeException $e) {
            return new DataResponse(['error' => $e->getMessage()], 404);
        }
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getCommits(string $owner, string $name, string $branch): DataResponse {
        try {
            return new DataResponse($this->repoService->getCommits($owner, $name, $branch));
        } catch (RuntimeException $e) {
            return new DataResponse(['error' => $e->getMessage()], 404);
        }
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getTree(string $owner, string $name, string $branch): DataResponse {
        $path = (string) ($this->request->getParam('path') ?? '');
        try {
            return new DataResponse($this->repoService->getTree($owner, $name, $branch, $path));
        } catch (RuntimeException $e) {
            return new DataResponse(['error' => $e->getMessage()], 404);
        }
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getBlob(string $owner, string $name, string $branch): DataResponse {
        $path = (string) ($this->request->getParam('path') ?? '');
        try {
            return new DataResponse(['content' => $this->repoService->getBlob($owner, $name, $branch, $path)]);
        } catch (RuntimeException $e) {
            return new DataResponse(['error' => $e->getMessage()], 404);
        }
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getRepo(string $owner, string $name): DataResponse {
        try {
            return new DataResponse($this->repoService->getRepo($owner, $name));
        } catch (RuntimeException $e) {
            return new DataResponse(['error' => $e->getMessage()], 404);
        }
    }

    #[NoAdminRequired]
    public function updateRepo(string $owner, string $name): DataResponse {
        $fields = $this->request->getParams();
        unset($fields['owner'], $fields['name'], $fields['_route']);
        try {
            return new DataResponse($this->repoService->updateRepo($name, $fields));
        } catch (RuntimeException $e) {
            return new DataResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[NoAdminRequired]
    public function transferRepo(string $owner, string $name): DataResponse {
        $newOwner = trim((string) ($this->request->getParam('new_owner') ?? ''));
        if ($newOwner === '') {
            return new DataResponse(['error' => 'new_owner is required'], 400);
        }
        try {
            return new DataResponse($this->repoService->transferRepo($name, $newOwner));
        } catch (RuntimeException $e) {
            return new DataResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getGraph(string $owner, string $name): DataResponse {
        try {
            return new DataResponse($this->repoService->getGraph($owner, $name));
        } catch (RuntimeException $e) {
            return new DataResponse(['error' => $e->getMessage()], 404);
        }
    }

    #[NoAdminRequired]
    public function uploadFiles(string $owner, string $name): DataResponse {
        $params    = $this->request->getParams();
        $branch    = trim((string) ($params['branch']    ?? ''));
        $directory = trim((string) ($params['directory'] ?? ''));
        $message   = trim((string) ($params['message']   ?? ''));
        $rawFiles  = $params['files'] ?? [];

        if (!is_array($rawFiles) || count($rawFiles) === 0) {
            return new DataResponse(['error' => 'No files provided'], 400);
        }

        $files = [];
        foreach ($rawFiles as $f) {
            $fileName = basename((string) ($f['name'] ?? ''));
            $b64      = (string) ($f['content'] ?? '');
            if ($fileName === '' || $b64 === '') continue;
            $decoded = base64_decode($b64, strict: true);
            if ($decoded === false) {
                return new DataResponse(['error' => "Invalid base64 for file: {$fileName}"], 400);
            }
            $files[] = ['name' => $fileName, 'content' => $decoded];
        }

        if (count($files) === 0) {
            return new DataResponse(['error' => 'No valid files'], 400);
        }

        try {
            $this->repoService->uploadFiles($owner, $name, $branch, $directory, $files, $message);
            return new DataResponse(['ok' => true, 'committed' => count($files)]);
        } catch (RuntimeException $e) {
            return new DataResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getReadme(string $owner, string $name): DataResponse {
        $repo   = $this->repoService->getRepo($owner, $name);
        $branch = $repo['default_branch'] ?? 'main';
        try {
            $content = $this->repoService->getReadme($owner, $name, $branch);
            return new DataResponse(['content' => $content]);
        } catch (RuntimeException $e) {
            return new DataResponse(['error' => $e->getMessage()], 404);
        }
    }
}

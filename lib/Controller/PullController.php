<?php

declare(strict_types=1);

namespace OCA\Git\Controller;

use OCA\Git\Service\PullService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use RuntimeException;

class PullController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private PullService $pullService,
    ) {
        parent::__construct($appName, $request);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function listPulls(string $owner, string $name): DataResponse {
        $state = (string) ($this->request->getParam('state') ?? 'open');
        try {
            return new DataResponse($this->pullService->listPulls($owner, $name, $state));
        } catch (RuntimeException $e) {
            return new DataResponse(['error' => $e->getMessage()], 404);
        }
    }

    #[NoAdminRequired]
    public function createPull(string $owner, string $name): DataResponse {
        $params = $this->request->getParams();
        $title  = trim((string) ($params['title']       ?? ''));
        $body   = trim((string) ($params['body']        ?? ''));
        $head   = trim((string) ($params['head_branch'] ?? ''));
        $base   = trim((string) ($params['base_branch'] ?? ''));
        if ($title === '' || $head === '' || $base === '') {
            return new DataResponse(['error' => 'title, head_branch and base_branch are required'], 400);
        }
        try {
            return new DataResponse($this->pullService->createPull($owner, $name, $title, $body, $head, $base), 201);
        } catch (RuntimeException $e) {
            return new DataResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getPull(string $owner, string $name, int $number): DataResponse {
        try {
            return new DataResponse($this->pullService->getPull($owner, $name, $number));
        } catch (RuntimeException $e) {
            return new DataResponse(['error' => $e->getMessage()], 404);
        }
    }

    #[NoAdminRequired]
    public function mergePull(string $owner, string $name, int $number): DataResponse {
        try {
            return new DataResponse($this->pullService->mergePull($owner, $name, $number));
        } catch (RuntimeException $e) {
            return new DataResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[NoAdminRequired]
    public function closePull(string $owner, string $name, int $number): DataResponse {
        try {
            return new DataResponse($this->pullService->closePull($owner, $name, $number));
        } catch (RuntimeException $e) {
            return new DataResponse(['error' => $e->getMessage()], 400);
        }
    }
}

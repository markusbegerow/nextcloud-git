<?php

declare(strict_types=1);

namespace OCA\Git\Controller;

use OCA\Git\Service\IssueService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use RuntimeException;

class IssueController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private IssueService $issueService,
    ) {
        parent::__construct($appName, $request);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function listIssues(string $owner, string $name): DataResponse {
        $state = (string) ($this->request->getParam('state') ?? 'open');
        try {
            return new DataResponse($this->issueService->listIssues($owner, $name, $state));
        } catch (RuntimeException $e) {
            return new DataResponse(['error' => $e->getMessage()], 404);
        }
    }

    #[NoAdminRequired]
    public function createIssue(string $owner, string $name): DataResponse {
        $params = $this->request->getParams();
        $title  = trim((string) ($params['title'] ?? ''));
        $body   = trim((string) ($params['body']  ?? ''));
        if ($title === '') {
            return new DataResponse(['error' => 'Title is required'], 400);
        }
        try {
            return new DataResponse($this->issueService->createIssue($owner, $name, $title, $body), 201);
        } catch (RuntimeException $e) {
            return new DataResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getIssue(string $owner, string $name, int $number): DataResponse {
        try {
            return new DataResponse($this->issueService->getIssue($owner, $name, $number));
        } catch (RuntimeException $e) {
            return new DataResponse(['error' => $e->getMessage()], 404);
        }
    }

    #[NoAdminRequired]
    public function updateIssue(string $owner, string $name, int $number): DataResponse {
        $fields = $this->request->getParams();
        unset($fields['owner'], $fields['name'], $fields['number'], $fields['_route']);
        try {
            return new DataResponse($this->issueService->updateIssue($owner, $name, $number, $fields));
        } catch (RuntimeException $e) {
            return new DataResponse(['error' => $e->getMessage()], 400);
        }
    }
}

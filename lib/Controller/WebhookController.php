<?php

declare(strict_types=1);

namespace OCA\Git\Controller;

use OCA\Git\Service\WebhookService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use RuntimeException;

class WebhookController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private WebhookService $webhookService,
    ) {
        parent::__construct($appName, $request);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function list(string $owner, string $name): DataResponse {
        try {
            return new DataResponse($this->webhookService->listWebhooks($owner, $name));
        } catch (RuntimeException $e) {
            return new DataResponse(['error' => $e->getMessage()], 404);
        }
    }

    #[NoAdminRequired]
    public function create(string $owner, string $name): DataResponse {
        $params = $this->request->getParams();
        $url    = trim((string) ($params['url']    ?? ''));
        $secret = trim((string) ($params['secret'] ?? ''));
        $events = (array)  ($params['events'] ?? ['push']);
        try {
            return new DataResponse($this->webhookService->createWebhook($owner, $name, $url, $secret, $events), 201);
        } catch (RuntimeException $e) {
            return new DataResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function delete(string $owner, string $name, int $id): DataResponse {
        try {
            $this->webhookService->deleteWebhook($owner, $name, $id);
            return new DataResponse(['ok' => true]);
        } catch (RuntimeException $e) {
            return new DataResponse(['error' => $e->getMessage()], 404);
        }
    }
}

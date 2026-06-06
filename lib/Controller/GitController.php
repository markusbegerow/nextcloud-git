<?php

declare(strict_types=1);

namespace OCA\Git\Controller;

use OCA\Git\Service\GitService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;
use RuntimeException;

class GitController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private GitService $gitService,
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * Git HTTP Smart Protocol — info/refs (clone/fetch discovery)
     */
    #[PublicPage]
    #[NoCSRFRequired]
    public function infoRefs(string $owner, string $repo): Response {
        $service = $this->request->getParam('service', '');

        // strip "git-" prefix if present
        $service = preg_replace('/^git-/', '', $service);

        if (!in_array($service, ['upload-pack', 'receive-pack'], true)) {
            $r = new DataResponse(['error' => 'Invalid service'], 400);
            return $r;
        }

        try {
            $body = $this->gitService->getInfoRefs($owner, $repo, $service);
        } catch (RuntimeException $e) {
            return new DataResponse(['error' => $e->getMessage()], 500);
        }

        $response = new Response();
        $response->addHeader('Content-Type', 'application/x-git-' . $service . '-advertisement');
        $response->addHeader('Cache-Control', 'no-cache');
        $response->addHeader('Pragma', 'no-cache');

        // Manually set body — Nextcloud's Response doesn't stream arbitrary binary,
        // so we render via a simple subclass trick
        return new class($body, 'application/x-git-' . $service . '-advertisement') extends Response {
            public function __construct(private string $raw, string $ct) {
                parent::__construct();
                $this->addHeader('Content-Type', $ct);
                $this->addHeader('Cache-Control', 'no-cache');
            }
            public function render(): string { return $this->raw; }
        };
    }

    /**
     * git-upload-pack (clone / fetch)
     */
    #[PublicPage]
    #[NoCSRFRequired]
    public function uploadPack(string $owner, string $repo): Response {
        return $this->runPack($owner, $repo, 'upload-pack');
    }

    /**
     * git-receive-pack (push)
     */
    #[PublicPage]
    #[NoCSRFRequired]
    public function receivePack(string $owner, string $repo): Response {
        return $this->runPack($owner, $repo, 'receive-pack');
    }

    private function runPack(string $owner, string $name, string $service): Response {
        $input = file_get_contents('php://input');
        try {
            $output = $this->gitService->runService($owner, $name, $service, $input);
        } catch (RuntimeException $e) {
            return new DataResponse(['error' => $e->getMessage()], 500);
        }

        return new class($output, 'application/x-git-' . $service . '-result') extends Response {
            public function __construct(private string $raw, string $ct) {
                parent::__construct();
                $this->addHeader('Content-Type', $ct);
                $this->addHeader('Cache-Control', 'no-cache');
            }
            public function render(): string { return $this->raw; }
        };
    }
}

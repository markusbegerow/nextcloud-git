<?php

declare(strict_types=1);

namespace OCA\Git\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IUserManager;

class SshAuthController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private IUserManager $userManager,
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * Validates an API token (app password) and returns the owning user.
     * The SSH shell script calls this to map a public key / token to a user.
     */
    #[PublicPage]
    #[NoCSRFRequired]
    public function auth(): DataResponse {
        // Basic auth carries the credentials from the shell script
        $user = $this->request->getParam('user', '');
        $pass = $this->request->getParam('pass', '');

        if ($user === '' || $pass === '') {
            return new DataResponse(['error' => 'Missing credentials'], 400);
        }

        $ncUser = $this->userManager->checkPasswordNoLogging($user, $pass);
        if ($ncUser === false) {
            return new DataResponse(['error' => 'Invalid credentials'], 403);
        }

        return new DataResponse(['owner_uid' => $ncUser->getUID()]);
    }
}

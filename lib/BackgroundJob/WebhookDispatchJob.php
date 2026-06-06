<?php

declare(strict_types=1);

namespace OCA\Git\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\ILogger;

class WebhookDispatchJob extends QueuedJob {
    public function __construct(
        ITimeFactory $time,
        private ILogger $logger,
    ) {
        parent::__construct($time);
    }

    /**
     * @param array{url: string, payload: string, signature: string} $argument
     */
    protected function run($argument): void {
        $url       = $argument['url']       ?? '';
        $payload   = $argument['payload']   ?? '';
        $signature = $argument['signature'] ?? '';

        if ($url === '') return;

        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => implode("\r\n", [
                    'Content-Type: application/json',
                    'X-NextGit-Event: push',
                    'X-NextGit-Signature: sha256=' . $signature,
                    'User-Agent: NextGit-Webhook/1.0',
                ]),
                'content'         => $payload,
                'timeout'         => 10,
                'ignore_errors'   => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            $this->logger->warning("NextGit webhook delivery failed for URL: $url", ['app' => 'git']);
        }
    }
}
